<?php

declare(strict_types=1);

return [
    'kafka' => [
        'consumer_host' => env('KAFKA_PROXY_CONSUMER', '127.0.0.1:8082'),
    ],
    'topic' => [
        // 告警记录同步到ES
        'alarmhistory_to_es' => env('KAFKA_TOPIC_ALARMHISTORY_TO_ES', 'jcjg_alarm_test_alarm_history'),
    ],
    'per_pull_num' => [
        // 告警记录，每次重队列pull数据条数
        'alarmhistory' => env('KAFKA_PERPULLNUM_ALARMHISTORY', 5),
    ],
];
