<?php

declare(strict_types=1);

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

return [
    // 告警任务ID
    'taskid' => (int) env('DOG_TASKID'),
    // 告警任务token
    'token' => env('DOG_TOKEN'),
    // 告警地址
    'base_uri' => env('DOG_BASE_URI'),
    /*
     * GuzzleHttp配置
     */
    'guzzle' => [
        // guzzle原生配置选项
        'options' => [
            'http_errors' => false,
            'connect_timeout' => 0,
            'timeout' => 0,
            // hyperf集成guzzle的swoole配置选项
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
        ],
        // guzzle中间件配置
        'middlewares' => [
            // 失败重试中间件
            'retry' => function ($container = null) {
                return Middleware::retry(
                    function ($retries, RequestInterface $request, ResponseInterface $response = null) {
                        if (
                            (! $response || $response->getStatusCode() >= 500) &&
                            $retries < 1
                        ) {
                            return true;
                        }
                        return false;
                    },
                    function () {
                        return 10;
                    }
                );
            },
            // // 请求日志记录中间件
            // 'logger' => function ($container = null) {
            //     // $format中{response}调用$response->getBody()会导致没有结果输出
            //     $format = ">>>>>>>>\n{request}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
            //     $formatter = new MessageFormatter($format);
            //     // 在其他框架将$logger进行正确替换即可
            //     // hyperf
            //     // $logger = \Hyperf\Utils\ApplicationContext::getContainer()
            //     //     ->get(\Hyperf\Logger\LoggerFactory::class)
            //     //     ->get('influx-guzzle');
            //     // laravel
            //     // $logger = \Illuminate\Support\Facades\Log::getLogger();

            //     return Middleware::log($logger, $formatter, 'debug');
            // }
        ],
        // hyperf集成guzzle的连接池配置选项，非hyperf框架忽略
        'pool' => [
            'option' => [
                'max_connections' => 200,
            ],
        ],
    ],

    /*
     * 哮天犬后台自身配置
     */
    // 模板配置
    'tpl' => [
        // 变量名
        'vars' => [
            'common.env',
        ],
        // 变量值
        'values' => [
            'common' => [
                'env' => env('DOG_VARS_COMMON_ENV', ''),
            ],
        ],
    ],
];
