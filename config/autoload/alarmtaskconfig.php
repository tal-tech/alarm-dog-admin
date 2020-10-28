<?php

declare(strict_types=1);

return [
    'default_rate_limit' => (int) env('ALARM_TASK_DEFAULT_RATE_LIMIT', 200),
];
