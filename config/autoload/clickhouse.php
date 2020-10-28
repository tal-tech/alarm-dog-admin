<?php

declare(strict_types=1);

return [
    'default' => [
        'host' => env('CLICKHOUSE_DEFAULT_HOST', '127.0.0.1'),
        'port' => env('CLICKHOUSE_DEFAULT_PORT', 8123),
        'username' => env('CLICKHOUSE_DEFAULT_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_DEFAULT_PASSWORD', ''),
        'database' => env('CLICKHOUSE_DEFAULT_DATABASE', 'alarm_platform'),
        'timeout' => (float) env('CLICKHOUSE_DEFAULT_TIMEOUT', 5),
        'connect_timeout' => (int) env('CLICKHOUSE_DEFAULT_CONNECT_TIMEOUT', 5),
    ],
    'sync' => [
        'history' => [
            // 多久之前的数据同步到clickhouse，单位：秒
            'until_time' => (int) env('CLICKHOUSE_SYNC_HISTORY_UNTIL_TIME', 3 * 86400),
            'batch_size' => (int) env('CLICKHOUSE_SYNC_BATCH_SIZE', 50000),
            // 同步一次休眠的秒数
            'sleep_time' => (float) env('CLICKHOUSE_SYNC_SLEEP_TIME', 2),
        ],
        'workflow' => [
            // 多久之前的数据同步到clickhouse，单位：秒
            'until_time' => (int) env('CLICKHOUSE_SYNC_WORKFLOW_UNTIL_TIME', 15 * 86400),
            'batch_size' => (int) env('CLICKHOUSE_SYNC_WORKFLOW_BATCH_SIZE', 50000),
            // 同步一次休眠的秒数
            'sleep_time' => (float) env('CLICKHOUSE_SYNC_WORKFLOW_SLEEP_TIME', 2),
        ],
        'workflow-pipeline' => [
            // 多久之前的数据同步到clickhouse，单位：秒
            'until_time' => (int) env('CLICKHOUSE_SYNC_WORKFLOW_PIPELINE_UNTIL_TIME', 15 * 86400),
            'batch_size' => (int) env('CLICKHOUSE_SYNC_WORKFLOW_PIPELINE_BATCH_SIZE', 50000),
            // 同步一次休眠的秒数
            'sleep_time' => (float) env('CLICKHOUSE_SYNC_WORKFLOW_PIPELINE_SLEEP_TIME', 2),
        ],
    ],
];
