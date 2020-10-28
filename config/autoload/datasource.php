<?php

declare(strict_types=1);

return [
    /*
     * ElasticSearch
     */
    'es' => [
        'enable_log' => env('DATASOURCE_ES_ENABLE_LOG', false),
    ],
    /*
     * Webhook
     */
    'webhook' => [
        'enable_log' => env('DATASOURCE_WEBHOOK_ENABLE_LOG', false),
        'guzzle' => [
            'headers' => [
                'User-Agent' => 'dog/1.1',
            ],
        ],

        // 网关配置
        'gw_appid' => env('DATASOURCE_WEBHOOK_GW_APPID', env('NOTICER_GATEWAY_APPID')),
        'gw_appkey' => env('DATASOURCE_WEBHOOK_GW_APPKEY', env('NOTICER_GATEWAY_APPKEY')),
    ],
];
