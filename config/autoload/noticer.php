<?php

declare(strict_types=1);

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Guzzle\RetryMiddleware;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;

return [
    'channel' => [
        // 钉钉工作通知配置
        'dingworker' => [
            'appid' => env('NOTICER_DINGWORKER_APPID'),
            'appkey' => env('NOTICER_DINGWORKER_APPKEY'),
            'uri_get_ticket' => env('NOTICER_DINGWORKER_URI_GET_TICKET', null),
            'uri_notice' => env('NOTICER_DINGWORKER_URI_NOTICE', null),
        ],
        // 钉钉机器人配置
        'dinggroup' => [
            // Redis冷却标记key前缀
            'sleep_redis_prefix' => env('NOTICER_DINGGROUP_SLEEP_REDIS_PREFIX', null),
            // 冷却次数，达到多少次之后开始冷却，钉钉官方20次，建议稍低
            'sleep_num' => (int) env('NOTICER_DINGGROUP_SLEEP_NUM', 19),
            // 冷却时间，官方60秒，建议稍长
            'sleep_time' => (int) env('NOTICER_DINGGROUP_SLEEP_TIME', 61),
        ],
        // Yach工作通知配置
        'yachworker' => [
            'appid' => env('NOTICER_YACHWORKER_APPID', env('NOTICER_DINGWORKER_APPID')),
            'appkey' => env('NOTICER_YACHWORKER_APPKEY', env('NOTICER_DINGWORKER_APPKEY')),
            'uri_get_ticket' => env('NOTICER_YACHWORKER_URI_GET_TICKET', null),
            'uri_notice' => env('NOTICER_YACHWORKER_URI_NOTICE', null),
        ],
        // Yach机器人配置
        'yachgroup' => [
            // Redis冷却标记key前缀
            'sleep_redis_prefix' => env('NOTICER_YACHGROUP_SLEEP_REDIS_PREFIX', null),
            // 冷却次数，达到多少次之后开始冷却，Yach官方20次，建议稍低
            'sleep_num' => (int) env('NOTICER_YACHGROUP_SLEEP_NUM', 19),
            // 冷却时间，官方60秒，建议稍长
            'sleep_time' => (int) env('NOTICER_YACHGROUP_SLEEP_TIME', 61),
            'uri_push' => env('NOTICER_YACHGROUP_URI_PUSH', null),
        ],
        // 短信通知配置
        'sms' => [
            // 短信应用appid
            'appid' => env('NOTICER_SMS_APPID'),
            // 短信应用secret
            'secret' => env('NOTICER_SMS_SECRET'),
            // baseUri
            'base_uri' => env('NOTICER_SMS_BASE_URI', null),
        ],
        // 电话通知配置，使用的容联云的tts
        'phone' => [
            'base_uri' => 'https://app.cloopen.com:8883/2013-12-26/',
            'appid' => env('NOTICER_YUNTONGXUN_APPID'),
            'sid' => env('NOTICER_YUNTONGXUN_SID'),
            'token' => env('NOTICER_YUNTONGXUN_TOKEN'),
            // 容联云的配置选项，更多请参考：https://www.yuntongxun.com/doc/rest/tongzhi/3_5_1_1.html
            'options' => [
                'txtSpeed' => '-25',
                'displayNum' => env('NOTICER_YUNTONGXUN_DISPLAY_NUMBER'),
            ],
        ],
        // 邮件通知配置
        'email' => [
            // 暂时只支持smtp协议方式
            'driver' => 'smtp',
            'host' => env('NOTICER_EMAIL_SMTP_HOST'),
            'port' => env('NOTICER_EMAIL_SMTP_PORT'),
            'username' => env('NOTICER_EMAIL_USERNAME'),
            'password' => env('NOTICER_EMAIL_PASSWORD'),
            'encryption' => env('NOTICER_EMAIL_ENCRYPTION'),
            'from_address' => env('NOTICER_EMAIL_FROM_ADDRESS', env('NOTICER_EMAIL_USERNAME')),
            'from_name' => env('NOTICER_EMAIL_FROM_NAME'),
        ],
    ],
    // guzzle配置，http请求都用此工具
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
                return make(RetryMiddleware::class, [
                    'retries' => 1,
                    'delay' => 10,
                ])->getMiddleware();
            },
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
    ],
];
