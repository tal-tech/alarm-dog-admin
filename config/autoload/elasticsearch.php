<?php

declare(strict_types=1);

return [
    'default' => [
        'cluster' => env('ELASTICSEARCH_CLUSTER', '127.0.0.1:9200'),
    ],
];
