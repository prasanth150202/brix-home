<?php
declare(strict_types=1);

use App\Ai\ChatService;
use App\Ai\ClientGone;

/**
 * POST /api/chat with {"message": "...", "conversation": "<token>|null"}.
 * Answers as a stream of server-sent events so text appears as it is written.
 */
function api_chat(): void
{
    $body = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($body)) {
        json_response(['error' => 'Bad request.'], 400);
    }
    if (!same_origin()) {
        json_response(['error' => 'Requests from other sites are not allowed.'], 403);
    }
    if (!chat_token_valid((string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
        json_response(['error' => 'This page is out of date. Refresh it and try again.'], 419);
    }
    // Release the session lock (if any) so other requests are not blocked while this streams.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    ignore_user_abort(true);
    set_time_limit(180);
    @ini_set('zlib.output_compression', '0');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache, no-store, no-transform');
    header('X-Accel-Buffering: no');
    // Padding pushes the first event past buffers in some proxies.
    echo ':' . str_repeat(' ', 2048) . "\n\n";
    flush();

    $send = static function (string $event, array $data): void {
        echo "event: $event\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n\n";
        flush();
        if (connection_aborted()) {
            throw new ClientGone();
        }
    };

    try {
        $conversation = isset($body['conversation']) && is_string($body['conversation']) ? $body['conversation'] : null;
        (new ChatService($send))->handle((string) ($body['message'] ?? ''), $conversation);
    } catch (ClientGone) {
        // The visitor left; nothing more to send.
    } catch (Throwable $ex) {
        log_message('ai', 'Unhandled chat error: ' . $ex->getMessage(), ['file' => $ex->getFile() . ':' . $ex->getLine()]);
        if (!connection_aborted()) {
            echo "event: error\ndata: " . json_encode(['message' => (string) setting('ai_unavailable_reply')]) . "\n\n";
            flush();
        }
    }
}

/** POST /api/track: records a click on an install button. */
function api_track(): void
{
    if (!same_origin()) {
        http_response_code(403);
        return;
    }
    $body = json_decode((string) file_get_contents('php://input'), true);
    $body = is_array($body) ? $body : [];

    $productId = (int) ($body['product'] ?? 0);
    if ($productId && !db_val('SELECT id FROM products WHERE id = ?', [$productId])) {
        $productId = 0;
    }
    $conversationId = null;
    if (isset($body['conversation']) && is_string($body['conversation']) && preg_match('/^[a-f0-9]{32}$/', $body['conversation'])) {
        $conversationId = db_val('SELECT id FROM chat_conversations WHERE token = ?', [$body['conversation']]);
    }
    $source = (string) ($body['source'] ?? '');
    $allowed = ['chat', 'hero', 'header', 'pricing', 'cta', 'blog', 'page', 'footer', 'apps'];

    db_insert('cta_clicks', [
        'product_id'      => $productId ?: null,
        'conversation_id' => $conversationId ?: null,
        'source'          => in_array($source, $allowed, true) ? $source : 'other',
        'url'             => mb_substr((string) ($body['url'] ?? ''), 0, 500),
        'ip_hash'         => ip_hash(),
        'created_at'      => now(),
    ]);
    http_response_code(204);
}
