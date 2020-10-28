<?php

declare(strict_types=1);

return [
    'pool' => env('CAPTCHA_POOL', '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM'),
    'length' => (int) env('CAPTCHA_LENGTH', 5),
    'width' => (int) env('CAPTCHA_WIDTH', 150),
    'height' => (int) env('CAPTCHA_HEIGHT', 40),
    'cache_prefix' => env('CAPTCHA_CACHE_PREFIX', 'dog.secure.captcha.'),
    'expire' => (int) env('CAPTCHA_EXPIRE', 300),
];
