<?php
declare(strict_types=1);

/**
 * A tiny router. Patterns use {name} for one path segment, {id} for digits
 * and {name*} for the rest of the path (slashes included).
 */
function route(string $methods, string $pattern, callable $handler): void
{
    $regex = preg_replace_callback('/\{(\w+)(\*)?\}/', static function (array $m): string {
        if (!empty($m[2])) {
            return '(?P<' . $m[1] . '>.+)';
        }
        return $m[1] === 'id' ? '(?P<id>\d+)' : '(?P<' . $m[1] . '>[^/]+)';
    }, $pattern);

    $GLOBALS['__routes'][] = [
        'methods' => explode('|', strtoupper($methods)),
        'regex'   => '#^' . $regex . '$#',
        'handler' => $handler,
    ];
}

function dispatch(string $method, string $path): void
{
    $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
    foreach ($GLOBALS['__routes'] ?? [] as $route) {
        if (!preg_match($route['regex'], $path, $m)) {
            continue;
        }
        if (!in_array($method, $route['methods'], true)) {
            continue;
        }
        $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
        ($route['handler'])(...$params);
        return;
    }
    abort(404);
}
