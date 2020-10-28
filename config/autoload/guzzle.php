<?php

declare(strict_types=1);

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Guzzle\RetryMiddleware;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

// guzzle配置，http请求都用此工具
return [
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
        // 'retry' => function () {
        //     return make(RetryMiddleware::class, [
        //         'retries' => 1,
        //         'delay' => 10,
        //     ])->getMiddleware();
        // },
        // 请求日志记录中间件
        'logger' => function () {
            // $format中{response}调用$response->getBody()会导致没有结果输出
            $format = ">>>>>>>>\n{request}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
            $formatter = new MessageFormatter($format);
            $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('guzzle');

            return Middleware::log($logger, $formatter, 'info');
        },
    ],
    // hyperf集成guzzle的连接池配置选项
    'pool' => [
        'option' => [
            'max_connections' => 50,
        ],
    ],
];
