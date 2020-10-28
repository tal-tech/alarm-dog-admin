<?php

declare(strict_types=1);

return [
    // 同环比配置
    'cycle_compare' => [
        'data_init_push_url' => env(
            'MONITOR_CYCLE_COMPARE_DATA_INIT_PUSH_URL',
            env('INDEX_URL', 'http://127.0.0.1:9501') . '/api/monitor/cyclecompare/datainitbypush?id=:id&access_token=:access_token'
        ),
    ],
    // 监控记录每批删除数量
    'record' => [
        'batch_delete_size' => (int) env('MONITOR_RECORD_BATCH_DELETE_SIZE', 50000),
        'delete_sleep_time' => (float) env('MONITOR_RECORD_DELETE_SLEEP_TIME', 0.1),
    ],
];
