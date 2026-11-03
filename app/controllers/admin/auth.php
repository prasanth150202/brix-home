<?php
declare(strict_types=1);

function admin_login(): void
{
    if (current_admin()) {
        redirect('/admin');
    }

    $error = null;
    if (is_post()) {
        verify_csrf();
        if (login_throttled()) {
            $error = 'Too many failed attempts. Wait 15 minutes and try again.';
        } elseif (attempt_login((string) input('email'), (string) ($_POST['password'] ?? ''))) {
            $next = $_SESSION['after_login'] ?? '/admin';
            unset($_SESSION['after_login']);
            redirect(str_starts_with($next, '/admin') ? $next : '/admin');
        } else {
            $error = 'That email and password do not match an account.';
        }
    }

    render('admin/login', ['error' => $error, 'email' => (string) input('email', ''), 'title' => 'Sign in'], null);
}

function admin_logout(): void
{
    verify_csrf();
    logout_admin();
    redirect('/admin/login');
}

function admin_dashboard(): void
{
    require_admin();
    $since = date('Y-m-d H:i:s', time() - 7 * 86400);

    $stats = [
        'chats_week'   => (int) db_val('SELECT COUNT(*) FROM chat_conversations WHERE created_at > ?', [$since]),
        'questions'    => (int) db_val("SELECT COUNT(*) FROM chat_messages WHERE role = 'user' AND created_at > ?", [$since]),
        'off_topic'    => (int) db_val("SELECT COUNT(*) FROM chat_messages WHERE role = 'assistant' AND is_off_topic = 1 AND created_at > ?", [$since]),
        'clicks_week'  => (int) db_val('SELECT COUNT(*) FROM cta_clicks WHERE created_at > ?', [$since]),
        'documents'    => (int) db_val('SELECT COUNT(*) FROM kb_documents WHERE is_active = 1'),
        'chunks'       => (int) db_val('SELECT COUNT(*) FROM kb_chunks c JOIN kb_documents d ON d.id = c.document_id WHERE d.is_active = 1'),
        'posts'        => (int) db_val("SELECT COUNT(*) FROM posts WHERE status = 'published'"),
        'pages'        => (int) db_val("SELECT COUNT(*) FROM pages WHERE status = 'published'"),
    ];

    $recommended = db_all(
        "SELECT p.name, COUNT(m.id) AS times
           FROM chat_messages m JOIN products p ON p.id = m.product_id
          WHERE m.created_at > ? GROUP BY p.id ORDER BY times DESC LIMIT 6",
        [$since]
    );
    $clicks = db_all(
        "SELECT COALESCE(p.name, 'General install link') AS name, COUNT(c.id) AS clicks
           FROM cta_clicks c LEFT JOIN products p ON p.id = c.product_id
          WHERE c.created_at > ? GROUP BY c.product_id ORDER BY clicks DESC LIMIT 6",
        [$since]
    );
    $recent = db_all(
        'SELECT c.id, c.first_question, c.message_count, c.created_at, p.name AS product_name
           FROM chat_conversations c LEFT JOIN products p ON p.id = c.recommended_product_id
          ORDER BY c.id DESC LIMIT 8'
    );
    $provider = setting('ai_provider') === 'openrouter' ? 'openrouter' : 'anthropic';
    $hasKey = trim((string) setting($provider . '_api_key')) !== '' || (string) getenv($provider === 'openrouter' ? 'OPENROUTER_API_KEY' : 'ANTHROPIC_API_KEY') !== '';

    render('admin/dashboard', compact('stats', 'recommended', 'clicks', 'recent', 'provider', 'hasKey') + ['title' => 'Dashboard'], 'admin/layout');
}
