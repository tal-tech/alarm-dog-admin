<?php

declare(strict_types=1);

return [
    /*
     * 拼音转换配置
     */
    'pinyin' => [
        // 其他名称类转换选项
        'optConvert' => (int) PINYIN_UMLAUT_V | PINYIN_NO_TONE | PINYIN_KEEP_NUMBER | PINYIN_KEEP_ENGLISH |
        PINYIN_KEEP_PUNCTUATION,
        // 姓名类转换选项
        'optName' => (int) PINYIN_UMLAUT_V | PINYIN_NO_TONE | PINYIN_KEEP_NUMBER | PINYIN_KEEP_ENGLISH |
        PINYIN_KEEP_PUNCTUATION,
    ],

    'jwt_redirect_uri' => env('JWT_REDIRECT_URI', '/admin/#/?token=%s'),
    'login_noticer_channels' => env('LOGIN_NOTICER_CHANNELS', null),
    'login_captcha_ttl' => (int) env('LOGIN_CAPTCHA_TTL', 300),
    'login_captcha_cache_prefix' => 'alarm-dog-captcha.',

    /*
     * 是否启用调试模式，如果启用，将值设为true，如果不启用，将值设为false，如果同时兼容，将值设为auto
     */
    'jwt_debug' => env('JWT_DEBUG', false),
    // 填写要模拟的工号，例如1
    'jwt_debug_uid' => env('JWT_DEBUG_UID'),

    // 后台首页地址
    'index_url' => env('INDEX_URL', 'http://127.0.0.1:9501'),
    // 接口API的地址
    'base_uri_api' => env('BASE_URI_API', 'http://127.0.0.1:9502'),
    // 接口API告警的地址
    'base_uri_api_alarm' => env('BASE_URI_API_ALARM', env('BASE_URI_API', 'http://127.0.0.1:9502')),
];
