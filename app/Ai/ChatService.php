<?php
declare(strict_types=1);

namespace App\Ai;

use App\Kb\KnowledgeBase;
use Closure;
use Throwable;

/**
 * One chat turn: rate limit, retrieval, the model call, the product and
 * source cards, and logging. Events go out through $emit as they happen:
 * start, delta, reset, done or error.
 */
final class ChatService
{
    /** @param Closure(string, array): void $emit */
    public function __construct(private Closure $emit)
    {
    }

    public function handle(string $question, ?string $token): void
    {
        $started = microtime(true);
        $question = trim((string) preg_replace('/[ \t]+/u', ' ', $question));
        if ($question === '') {
            $this->emit('error', ['message' => 'Type a question first.']);
            return;
        }
        $question = mb_substr($question, 0, max(100, (int) setting('ai_max_question_chars', 1000)));

        $ipHash = ip_hash();
        $limit = (int) setting('ai_rate_limit_per_hour', 30);
        if ($limit > 0) {
            $recent = (int) db_val(
                "SELECT COUNT(*) FROM chat_messages WHERE ip_hash = ? AND role = 'user' AND created_at > ?",
                [$ipHash, date('Y-m-d H:i:s', time() - 3600)]
            );
            if ($recent >= $limit) {
                $this->emit('error', ['message' => "You've asked a lot of questions in the last hour. Please come back a little later."]);
                return;
            }
        }

        $conversation = $this->conversation($token, $ipHash, $question);
        $history = $this->history((int) $conversation['id']);
        $this->save($conversation, 'user', $question, ['ip_hash' => $ipHash]);
        $this->emit('start', ['conversation' => $conversation['token']]);

        $offTopic = trim((string) setting('ai_off_topic_reply'));

        if (Guardrails::isInjection($question)) {
            $this->emit('delta', ['t' => $offTopic]);
            $this->complete($conversation, $offTopic, null, [], true, ['provider' => 'guardrail', 'model' => null], $started);
            return;
        }

        $chunks = max(1, min(12, (int) setting('ai_context_chunks', 6)));
        $passages = KnowledgeBase::search($question, $chunks);
        // A follow-up like "how do I set that up?" finds more with the previous question attached.
        $previous = $this->lastQuestion($history);
        if (count($passages) < 2 && $previous !== null) {
            $passages = KnowledgeBase::search($question . ' ' . $previous, $chunks);
        }

        $products = active_products();
        $provider = self::provider($passages, $products, $question);
        $system = PromptBuilder::system($products, KnowledgeBase::caseStudyIndex());
        $messages = [...$history, ['role' => 'user', 'content' => PromptBuilder::userTurn($question, $passages)]];

        $filter = new MarkerFilter(fn (string $text) => $this->emit('delta', ['t' => $text]));
        $onReset = function () use ($filter): void {
            $filter->reset();
            $this->emit('reset', []);
        };

        try {
            $usage = $provider->stream($system, $messages, fn (string $text) => $filter->push($text), $onReset);
        } catch (ClientGone) {
            $this->save($conversation, 'assistant', $filter->text() ?: '(stopped before the answer started)', [
                'provider' => $provider->name(),
                'error'    => 'Visitor stopped the answer',
            ]);
            return;
        } catch (Throwable $ex) {
            log_message('ai', 'Chat request failed: ' . $ex->getMessage(), ['provider' => $provider->name()]);
            $partial = $filter->text();
            $reply = (string) setting('ai_unavailable_reply');
            $this->emit('error', ['message' => $reply, 'partial' => $partial !== '']);
            $this->save($conversation, 'assistant', $partial !== '' ? $partial : $reply, [
                'provider' => $provider->name(),
                'error'    => mb_substr($ex->getMessage(), 0, 500),
            ]);
            return;
        }

        [$text, $marker] = $filter->finish();

        if ($usage['stop_reason'] === 'refusal' || $text === '') {
            $this->emit('reset', []);
            $this->emit('delta', ['t' => $offTopic]);
            $text = $offTopic;
            $marker = ['app' => 'none', 'sources' => []];
        }

        // Off-topic replies are the off-topic message itself. app=none on its own only
        // means no product fits, since answers no longer have to recommend one.
        $offTopicStart = mb_substr($offTopic, 0, 60);
        $isOffTopic = $offTopicStart !== '' && str_contains($text, $offTopicStart);

        $product = null;
        $sources = [];
        if (!$isOffTopic) {
            // Only the tool app, or an app the merchant asked about by name, gets a card.
            $slug = $marker['app'] ?? null;
            $named = ProductMatcher::named($question, $products);
            if ($slug !== null && ($slug === ProductMatcher::TOOL_SLUG || $slug === ($named['slug'] ?? null))) {
                $product = find_product_by_slug($slug);
            }
            $sources = $this->sources($passages, $marker['sources'] ?? null);
        }

        $this->complete($conversation, $text, $product, $sources, $isOffTopic, [
            'provider'          => $provider->name(),
            'model'             => $usage['model'],
            'input_tokens'      => $usage['input_tokens'],
            'output_tokens'     => $usage['output_tokens'],
            'cache_read_tokens' => $usage['cache_read_tokens'],
        ], $started);
    }

    /** The configured provider, or demo mode when it has no API key. */
    public static function provider(array $passages = [], array $products = [], string $question = '', ?int $maxTokens = null): ChatProvider
    {
        $maxTokens ??= max(512, (int) setting('ai_max_tokens', 4000));

        if (setting('ai_provider') === 'openrouter') {
            $key = trim((string) setting('openrouter_api_key')) ?: (string) getenv('OPENROUTER_API_KEY');
            if ($key !== '') {
                return new OpenRouterProvider(
                    $key,
                    trim((string) setting('openrouter_model')),
                    trim((string) setting('openrouter_fallback_model')),
                    (float) setting('openrouter_temperature', 0.3),
                    $maxTokens,
                    absolute_url('/'),
                    (string) setting('site_name', 'BRIX'),
                );
            }
        } else {
            $key = trim((string) setting('anthropic_api_key')) ?: (string) getenv('ANTHROPIC_API_KEY');
            if ($key !== '') {
                $model = (string) setting('anthropic_model');
                $effort = (string) setting('anthropic_effort', 'low');
                return new AnthropicProvider(
                    $key,
                    array_key_exists($model, anthropic_models()) ? $model : 'claude-opus-5-5',
                    in_array($effort, ['low', 'medium', 'high'], true) ? $effort : 'low',
                    $maxTokens,
                );
            }
        }

        return new DemoProvider($passages, $products, $question, (string) setting('ai_off_topic_reply'));
    }

    private function emit(string $event, array $data): void
    {
        ($this->emit)($event, $data);
    }

    private function conversation(?string $token, string $ipHash, string $question): array
    {
        if ($token !== null && preg_match('/^[a-f0-9]{32}$/', $token)) {
            $found = db_one('SELECT * FROM chat_conversations WHERE token = ?', [$token]);
            if ($found) {
                return $found;
            }
        }
        $row = [
            'token'           => bin2hex(random_bytes(16)),
            'ip_hash'         => $ipHash,
            'user_agent'      => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'first_question'  => mb_substr($question, 0, 255),
            'message_count'   => 0,
            'created_at'      => now(),
            'last_message_at' => now(),
        ];
        $row['id'] = db_insert('chat_conversations', $row);
        return $row;
    }

    /** Earlier turns of this conversation, oldest first, as plain text. */
    private function history(int $conversationId): array
    {
        $limit = max(0, min(20, (int) setting('ai_history_messages', 6)));
        if ($limit === 0) {
            return [];
        }
        $rows = array_reverse(db_all(
            "SELECT role, content FROM chat_messages
              WHERE conversation_id = ? AND error IS NULL
              ORDER BY id DESC LIMIT $limit",
            [$conversationId]
        ));
        // The API needs the history to start with the visitor and alternate turns.
        $messages = [];
        foreach ($rows as $row) {
            $role = $row['role'] === 'assistant' ? 'assistant' : 'user';
            if (!$messages && $role === 'assistant') {
                continue;
            }
            if ($messages && end($messages)['role'] === $role) {
                array_pop($messages);
            }
            $messages[] = ['role' => $role, 'content' => $row['content']];
        }
        if ($messages && end($messages)['role'] === 'user') {
            array_pop($messages);
        }
        return $messages;
    }

    private function lastQuestion(array $history): ?string
    {
        for ($i = count($history) - 1; $i >= 0; $i--) {
            if ($history[$i]['role'] === 'user') {
                return $history[$i]['content'];
            }
        }
        return null;
    }

    /** Case-study cards for the excerpts the answer relied on. */
    private function sources(array $passages, ?array $numbers): array
    {
        $picked = [];
        if ($numbers === null) {
            // No closing line: fall back to the best-matching case studies.
            $picked = array_slice($passages, 0, 3);
        } else {
            foreach ($numbers as $n) {
                if (isset($passages[$n - 1])) {
                    $picked[] = $passages[$n - 1];
                }
            }
        }

        $cards = [];
        foreach ($picked as $passage) {
            $id = $passage['document_id'];
            if (isset($cards[$id]) || $passage['doc_type'] !== 'case_study') {
                continue;
            }
            $cards[$id] = [
                'id'           => $id,
                'title'        => $passage['title'],
                'brand'        => (string) ($passage['brand'] ?? ''),
                'industry'     => (string) ($passage['industry'] ?? ''),
                'result_value' => (string) ($passage['result_value'] ?? ''),
                'result_label' => (string) ($passage['result_label'] ?? ''),
                'url'          => $passage['public_url'] ? url($passage['public_url']) : '',
            ];
        }
        return array_slice(array_values($cards), 0, 3);
    }

    private function complete(array $conversation, string $text, ?array $product, array $sources, bool $offTopic, array $meta, float $started): void
    {
        $messageId = $this->save($conversation, 'assistant', $text, $meta + [
            'product_id'   => $product['id'] ?? null,
            'sources'      => $sources ? json_encode(array_map(static fn ($s) => ['id' => $s['id'], 'title' => $s['title']], $sources)) : null,
            'is_off_topic' => $offTopic ? 1 : 0,
            'latency_ms'   => (int) round((microtime(true) - $started) * 1000),
        ]);
        if ($product) {
            db_update('chat_conversations', ['recommended_product_id' => $product['id']], 'id = ?', [$conversation['id']]);
        }
        $this->emit('done', [
            'message' => $messageId,
            'product' => $product ? product_payload($product) : null,
            'sources' => $sources,
        ]);
    }

    private function save(array $conversation, string $role, string $content, array $extra = []): int
    {
        $id = db_insert('chat_messages', [
            'conversation_id' => $conversation['id'],
            'role'            => $role,
            'content'         => $content,
            'created_at'      => now(),
        ] + $extra);
        db_exec(
            'UPDATE chat_conversations SET message_count = message_count + 1, last_message_at = ? WHERE id = ?',
            [now(), $conversation['id']]
        );
        return $id;
    }
}
