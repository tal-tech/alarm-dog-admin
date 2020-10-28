<?php

declare(strict_types=1);

return [
    'enable' => env('CORS_ENABLE', true),
    // 为true时，会优先使用origin的值，如果没有则为*
    'allowOrigin' => env('CORS_ALLOW_ORIGIN', true),
    'allowMethods' => env('CORS_ALLOW_METHOD', 'GET, POST, PUT, DELETE, PATCH, OPTIONS'),
    'allowCredentials' => env('CORS_ALLOW_CREDENTIALS', true),
    'allowHeaders' => env('CORS_ALLOW_HEADERS', '*,DNT,Keep-Alive,User-Agent,Cache-Control,Content-Type,Authorization'),
    'exposeHeaders' => env('CORS_EXPOSE_HEADERS', '*,Authorization'),
    'controlMaxAge' => env('CORS_CONTROL_MAX_AGE', 86400),
];
