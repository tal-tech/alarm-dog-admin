<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'read' => [
            'host' => env('DB_RO_HOST', 'localhost'),
            'port' => env('DB_RO_PORT', 3306),
            'database' => env('DB_RO_DATABASE', 'hyperf'),
            'username' => env('DB_RO_USERNAME', 'root'),
            'password' => env('DB_RO_PASSWORD', ''),
        ],
        'write' => [
            'host' => env('DB_RW_HOST', 'localhost'),
            'port' => env('DB_RW_PORT', 3306),
            'database' => env('DB_RW_DATABASE', 'hyperf'),
            'username' => env('DB_RW_USERNAME', 'root'),
            'password' => env('DB_RW_PASSWORD', ''),
        ],
        'sticky' => true,
        'charset' => env('DB_CHARSET', 'utf8'),
        'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
        ],
    ],
];
