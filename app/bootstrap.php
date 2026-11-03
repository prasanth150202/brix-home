<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('BRIX AI needs PHP 8.1 or newer. This server runs PHP ' . PHP_VERSION . '.');
}

if (!is_file(BASE_PATH . '/config/config.php')) {
    http_response_code(500);
    exit('Missing config/config.php. Run "php bin/install.php" first.');
}

require BASE_PATH . '/vendor/autoload.php';
require BASE_PATH . '/app/helpers.php';
require BASE_PATH . '/app/db.php';
require BASE_PATH . '/app/settings.php';
require BASE_PATH . '/app/auth.php';
require BASE_PATH . '/app/router.php';
require BASE_PATH . '/app/icons.php';
require BASE_PATH . '/app/content.php';
require BASE_PATH . '/app/media.php';
require BASE_PATH . '/app/resources.php';
require BASE_PATH . '/app/form.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));

error_reporting(E_ALL);
ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-error.log');
