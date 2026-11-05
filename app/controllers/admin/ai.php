<?php
declare(strict_types=1);

use App\Ai\ChatService;
use App\Ai\DemoProvider;

const AI_TEXT_SETTINGS = [
    'ai_assistant_name', 'openai_model', 'openrouter_model', 'openrouter_fallback_model', 'ai_extra_instructions',
    'ai_off_topic_reply', 'ai_unavailable_reply', 'kb_synonyms', 'chat_welcome_title', 'chat_welcome_text', 'chat_disclaimer',
];

function admin_ai_settings(): void
{
    require_admin('admin');
    $errors = [];

    if (is_post()) {
        verify_csrf();
        $values = [];
        foreach (AI_TEXT_SETTINGS as $key) {
            $values[$key] = trim((string) ($_POST[$key] ?? ''));
        }
        // These must never be blank on the site; an empty field restores the default.
        foreach (['ai_assistant_name', 'ai_unavailable_reply', 'chat_welcome_title'] as $key) {
            if ($values[$key] === '') {
                $values[$key] = setting_defaults()[$key];
            }
        }
        $values['ai_provider'] = in_array(input('ai_provider'), ['openai', 'openrouter'], true) ? (string) input('ai_provider') : 'anthropic';
        $model = (string) input('anthropic_model');
        $values['anthropic_model'] = array_key_exists($model, anthropic_models()) ? $model : 'claude-opus-5-5';
        $effort = (string) input('anthropic_effort');
        $values['anthropic_effort'] = in_array($effort, ['low', 'medium', 'high'], true) ? $effort : 'low';
        $values['openrouter_temperature'] = (string) max(0, min(1.5, (float) input('openrouter_temperature', 0.3)));
        $values['openai_temperature'] = (string) max(0, min(1.5, (float) input('openai_temperature', 0.3)));

        $numbers = [
            'ai_max_tokens'          => [512, 32000],
            'ai_context_chunks'      => [1, 12],
            'ai_history_messages'    => [0, 20],
            'ai_max_question_chars'  => [100, 4000],
            'ai_rate_limit_per_hour' => [0, 1000],
        ];
        foreach ($numbers as $key => [$min, $max]) {
            $values[$key] = (string) max($min, min($max, input_int($key, (int) setting($key))));
        }

        // A key field left empty keeps the saved key; the remove box clears it.
        foreach (['anthropic_api_key', 'openai_api_key', 'openrouter_api_key'] as $key) {
            $new = trim((string) ($_POST[$key] ?? ''));
            if (!empty($_POST['clear_' . $key])) {
                $values[$key] = '';
            } elseif ($new !== '') {
                $values[$key] = $new;
            }
        }

        if ($values['ai_off_topic_reply'] === '') {
            $errors['ai_off_topic_reply'] = 'Write the reply BRIX AI gives to off-topic questions.';
        }
        if ($values['ai_provider'] === 'openai' && $values['openai_model'] === '') {
            $errors['openai_model'] = 'Enter an OpenAI model id, e.g. gpt-5-mini';
        }
        if ($values['ai_provider'] === 'openrouter' && $values['openrouter_model'] === '') {
            $errors['openrouter_model'] = 'Enter an OpenRouter model id, e.g. google/gemma-4-26b-a4b-it:free';
        }

        if (!$errors) {
            settings_save($values);
            flash('success', 'AI settings saved.');
            redirect('/admin/ai');
        }
        remember_input();
    }

    render('admin/ai', ['title' => 'AI settings', 'errors' => $errors], 'admin/layout');
    forget_input();
}

/** Show which key is saved without revealing it. */
function masked_key(string $key): string
{
    $value = trim((string) setting($key));
    if ($value === '') {
        return '';
    }
    return str_repeat('•', 8) . substr($value, -4);
}

function admin_ai_test(): void
{
    require_admin('admin');
    if (!csrf_valid()) {
        json_response(['ok' => false, 'error' => 'Session expired. Refresh the page.'], 419);
    }
    session_write_close();

    $provider = ChatService::provider(maxTokens: 1024);
    if ($provider instanceof DemoProvider) {
        json_response(['ok' => false, 'error' => 'No API key is saved for the selected provider, so the chat runs in demo mode.']);
    }

    $started = microtime(true);
    $reply = '';
    try {
        $usage = $provider->stream(
            'You are a connection test for a website chat. Follow the instruction exactly.',
            [['role' => 'user', 'content' => 'Reply with exactly: Connection OK']],
            static function (string $text) use (&$reply): void {
                $reply .= $text;
            }
        );
    } catch (Throwable $ex) {
        json_response(['ok' => false, 'error' => $ex->getMessage()]);
    }

    json_response([
        'ok'       => true,
        'provider' => $provider->name(),
        'model'    => $usage['model'],
        'reply'    => trim($reply),
        'ms'       => (int) round((microtime(true) - $started) * 1000),
    ]);
}

function admin_chats(): void
{
    require_admin();
    $filter = (string) ($_GET['filter'] ?? '');
    $search = trim((string) ($_GET['q'] ?? ''));

    $where = ['1 = 1'];
    $params = [];
    if ($filter === 'offtopic') {
        $where[] = "c.id IN (SELECT conversation_id FROM chat_messages WHERE is_off_topic = 1)";
    } elseif ($filter === 'errors') {
        $where[] = "c.id IN (SELECT conversation_id FROM chat_messages WHERE error IS NOT NULL)";
    } elseif ($filter === 'clicked') {
        $where[] = 'c.id IN (SELECT conversation_id FROM cta_clicks WHERE conversation_id IS NOT NULL)';
    }
    if ($search !== '') {
        $where[] = 'c.id IN (SELECT conversation_id FROM chat_messages WHERE content LIKE ?)';
        $params[] = "%$search%";
    }
    $sqlWhere = implode(' AND ', $where);

    $pager = paginate((int) db_val("SELECT COUNT(*) FROM chat_conversations c WHERE $sqlWhere", $params), 25, max(1, (int) ($_GET['page'] ?? 1)));
    $conversations = db_all(
        "SELECT c.*, p.name AS product_name,
                (SELECT COUNT(*) FROM cta_clicks k WHERE k.conversation_id = c.id) AS clicks,
                (SELECT MAX(is_off_topic) FROM chat_messages m WHERE m.conversation_id = c.id) AS off_topic
           FROM chat_conversations c LEFT JOIN products p ON p.id = c.recommended_product_id
          WHERE $sqlWhere
          ORDER BY c.id DESC
          LIMIT {$pager['per_page']} OFFSET {$pager['offset']}",
        $params
    );

    render('admin/chats/index', compact('conversations', 'pager', 'filter', 'search') + ['title' => 'Conversations'], 'admin/layout');
}

function admin_chat_show(string $id): void
{
    require_admin();
    $conversation = db_one('SELECT * FROM chat_conversations WHERE id = ?', [(int) $id]) ?? abort(404);
    $messages = db_all(
        'SELECT m.*, p.name AS product_name FROM chat_messages m LEFT JOIN products p ON p.id = m.product_id
          WHERE m.conversation_id = ? ORDER BY m.id',
        [$conversation['id']]
    );
    $clicks = db_all(
        'SELECT k.*, p.name AS product_name FROM cta_clicks k LEFT JOIN products p ON p.id = k.product_id WHERE k.conversation_id = ? ORDER BY k.id',
        [$conversation['id']]
    );
    render('admin/chats/show', compact('conversation', 'messages', 'clicks') + ['title' => 'Conversation #' . $conversation['id']], 'admin/layout');
}

function admin_chat_delete(string $id): void
{
    require_admin('admin');
    verify_csrf();
    db_exec('DELETE FROM chat_conversations WHERE id = ?', [(int) $id]);
    flash('success', 'Conversation deleted.');
    redirect('/admin/chats');
}
