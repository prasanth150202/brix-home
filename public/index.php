<?php
declare(strict_types=1);

// With PHP's built-in server (serve.bat), let it send real files directly.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . rawurldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__) && $file !== __FILE__) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (str_starts_with(request_path(), '/admin')) {
    header('X-Frame-Options: SAMEORIGIN');
    header('Cache-Control: no-store');
}

// Visitors don't need a session; admins (and draft previews) do.
if (str_starts_with(request_path(), '/admin') || isset($_COOKIE['brix_session'])) {
    start_session();
}
require BASE_PATH . '/app/routes.php';
dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
