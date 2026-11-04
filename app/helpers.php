<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    static $config = null;
    $config ??= require CONFIG_FILE;

    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

/** Escape for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** The folder the site is served from ('' at a domain root, '/brix' in a subfolder). */
function base_url_path(): string
{
    static $base = null;
    if ($base === null) {
        // dirname() returns "\" for the root on Windows, so normalise its result too.
        $dir = str_replace('\\', '/', dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '')));
        $base = PHP_SAPI === 'cli' ? '' : rtrim($dir, '/.');
        // When the project root's .htaccess rewrites requests into public/, the script
        // runs as /public/index.php but visitors' URLs never contain /public.
        $requested = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (str_ends_with($base, '/public') && !str_starts_with($requested . '/', $base . '/')) {
            $base = substr($base, 0, -strlen('/public'));
        }
    }
    return $base;
}

function url(string $path = '/'): string
{
    if (preg_match('#^(https?:)?//|^mailto:|^tel:|^\##i', $path)) {
        return $path;
    }
    return base_url_path() . '/' . ltrim($path, '/');
}

function absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim((string) config('app.url', ''), '/') . '/' . ltrim($path, '/');
}

/** URL for a file in public/, with a cache-busting version from its modification time. */
function asset(string $path): string
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    $version = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $version;
}

/** URL for a stored media path ('uploads/2026/10/x.png'), or an external URL as-is. */
function media_url(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    return url($path);
}

function redirect(string $to, int $status = 302): never
{
    header('Location: ' . url($to), true, $status);
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === $host) {
        header('Location: ' . $ref, true, 302);
        exit;
    }
    redirect($fallback);
}

function request_path(): string
{
    $path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $base = base_url_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    return $path;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

function input_int(string $key, int $default = 0): int
{
    $value = input($key);
    return is_numeric($value) ? (int) $value : $default;
}

function input_bool(string $key): int
{
    return empty($_POST[$key]) ? 0 : 1;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_name('brix_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure'   => $secure,
        'cookie_path'     => base_url_path() === '' ? '/' : base_url_path() . '/',
        'use_strict_mode' => true,
    ]);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

function verify_csrf(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

/**
 * Token for the public chat API. Signed rather than stored, so visitors need
 * no session and a page left open for hours keeps working.
 */
function chat_token(): string
{
    $issued = (string) time();
    return $issued . '.' . hash_hmac('sha256', 'chat|' . $issued, (string) config('app.secret'));
}

function chat_token_valid(string $token, int $maxAge = 7 * 86400): bool
{
    [$issued, $signature] = array_pad(explode('.', $token, 2), 2, '');
    if (!ctype_digit($issued) || time() - (int) $issued > $maxAge) {
        return false;
    }
    return hash_equals(hash_hmac('sha256', 'chat|' . $issued, (string) config('app.secret')), $signature);
}

/** False when the browser says the request came from another site. */
function same_origin(): bool
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '' || $origin === 'null') {
        return true;
    }
    return parse_url($origin, PHP_URL_HOST) === parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/** Keep the submitted form so it can be shown again after a validation error. */
function remember_input(): void
{
    $_SESSION['old_input'] = $_POST;
    unset($_SESSION['old_input']['_csrf'], $_SESSION['old_input']['password']);
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function forget_input(): void
{
    unset($_SESSION['old_input']);
}

function slugify(string $text, string $separator = '-'): string
{
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    if (function_exists('transliterator_transliterate')) {
        $text = (string) transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
    }
    $text = strtolower($text);
    $text = (string) preg_replace('/[^a-z0-9]+/', $separator, $text);
    return trim($text, $separator);
}

/** Slug for a URL path that may contain folders, e.g. "legal/privacy-policy". */
function slugify_path(string $path): string
{
    $parts = array_filter(array_map('slugify', explode('/', $path)), static fn ($p) => $p !== '');
    return implode('/', $parts);
}

function str_limit(string $text, int $limit = 160, string $end = '…'): string
{
    $text = trim($text);
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    $cut = mb_substr($text, 0, $limit);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $limit * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.;:-") . $end;
}

function plain_text(?string $html): string
{
    $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</h2>', '</h3>'], ' ', (string) $html));
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

function lines(?string $text): array
{
    $out = [];
    foreach (preg_split('/\R/', (string) $text) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function format_date(?string $datetime, string $format = 'M j, Y'): string
{
    if (!$datetime) {
        return '';
    }
    $time = strtotime($datetime);
    return $time ? date($format, $time) : '';
}

function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - (int) strtotime($datetime);
    return match (true) {
        $diff < 60 => 'just now',
        $diff < 3600 => floor($diff / 60) . ' min ago',
        $diff < 86400 => floor($diff / 3600) . ' h ago',
        $diff < 604800 => floor($diff / 86400) . ' d ago',
        default => format_date($datetime),
    };
}

function money(mixed $amount): string
{
    $amount = (float) $amount;
    return floor($amount) == $amount ? number_format($amount) : number_format($amount, 2);
}

function client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/** Visitor IPs are stored only as a keyed hash. */
function ip_hash(): string
{
    return hash_hmac('sha256', client_ip(), (string) config('app.secret', 'brix'));
}

/** Render a template from views/ and return the HTML. */
function view(string $template, array $data = []): string
{
    $file = BASE_PATH . '/views/' . $template . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: $template");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    try {
        include $file;
    } catch (Throwable $ex) {
        ob_end_clean();
        throw $ex;
    }
    return (string) ob_get_clean();
}

/** Render a template inside a layout and send it. */
function render(string $template, array $data = [], ?string $layout = 'site/layout'): void
{
    $content = view($template, $data);
    echo $layout === null ? $content : view($layout, $data + ['content' => $content]);
}

function partial(string $template, array $data = []): void
{
    echo view($template, $data);
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function abort(int $status = 404, string $message = ''): never
{
    http_response_code($status);
    if (str_starts_with(request_path(), '/api/')) {
        json_response(['error' => $message ?: 'Not found'], $status);
    }
    if (str_starts_with(request_path(), '/admin') && current_admin()) {
        render('admin/error', ['status' => $status, 'message' => $message, 'title' => 'Error'], 'admin/layout');
        exit;
    }
    render('site/404', ['status' => $status, 'message' => $message, 'meta' => page_meta(['title' => 'Page not found', 'noindex' => true])]);
    exit;
}

/** Page numbers for a list view. */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    return ['total' => $total, 'per_page' => $perPage, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage];
}

/** Current query string with some keys replaced, for pagination and filter links. */
function query_with(array $changes): string
{
    $query = array_merge($_GET, $changes);
    $query = array_filter($query, static fn ($v) => $v !== null && $v !== '');
    return $query ? '?' . http_build_query($query) : '';
}

function log_message(string $channel, string $message, array $context = []): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if ($context) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    @file_put_contents(BASE_PATH . "/storage/logs/$channel.log", $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function reading_minutes(?string $html): int
{
    $words = str_word_count(plain_text($html));
    return max(1, (int) ceil($words / 220));
}

function human_filesize(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $size = (float) $bytes;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return ($i === 0 ? (string) $bytes : number_format($size, 1)) . ' ' . $units[$i];
}
