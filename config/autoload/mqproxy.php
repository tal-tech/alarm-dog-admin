<?php

declare(strict_types=1);

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Guzzle\RetryMiddleware;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

return [
    // 网关配置
    'appid' => env('MQPROXY_APPID', env('NOTICER_GATEWAY_APPID')),
    'appkey' => env('MQPROXY_APPKEY', env('NOTICER_GATEWAY_APPKEY')),
    /*
     * guzzle配置
     */
    'guzzle' => [
        // guzzle原生配置选项
        'options' => [
            'timeout' => 5.0,
            'verify' => false,
            'http_errors' => false,
            // hyperf集成guzzle的swoole配置选项
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
        ],
        // guzzle中间件配置
        'middlewares' => [
            // 失败重试中间件
            'retry' => function () {
                return make(
                    RetryMiddleware::class,
                    [
                        'retries' => 1,
                        'delay' => 10,
                    ]
                )->getMiddleware();
            },
            // 请求日志记录中间件
            'logger' => function () {
                $format = ">>>>>>>>\n{request}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
                $formatter = new MessageFormatter($format);
                $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('mqproxy');

                return Middleware::log($logger, $formatter, 'debug');
            },
        ],
        // hyperf集成guzzle的连接池配置选项
        'pool' => [
            'option' => [
                'max_connections' => 50,
            ],
        ],
    ],
];
