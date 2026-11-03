<?php
// Copy this file to config.php on each machine and fill in the values.
// config.php is ignored by git so credentials never end up in the repository.
return [
    'app' => [
        'name'     => 'BRIX AI',
        // Full public URL without a trailing slash. Used for the sitemap,
        // canonical links and share images.
        'url'      => 'http://localhost:8000',
        'debug'    => true,
        'timezone' => 'Asia/Kolkata',
        // Random string used to hash visitor IPs. bin/install.php generates one.
        'secret'   => 'change-me',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'brix_ai',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],
];
