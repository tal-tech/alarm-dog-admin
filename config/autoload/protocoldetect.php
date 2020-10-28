<?php

declare(strict_types=1);

return [
    /*
     * Http
     */
    'http' => [
        'enable_log' => env('PROTOCOL_DETECT_HTTP_ENABLE_LOG', false),
        'guzzle' => [
            'headers' => [
                'User-Agent' => 'dog/1.1',
            ],
            'http_errors' => false,
        ],

        // 网关配置
        'gw_appid' => env('PROTOCOL_DETECT_HTTP_GW_APPID', env('NOTICER_GATEWAY_APPID')),
        'gw_appkey' => env('PROTOCOL_DETECT_HTTP_GW_APPKEY', env('NOTICER_GATEWAY_APPKEY')),
    ],
];
