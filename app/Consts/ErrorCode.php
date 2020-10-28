<?php

declare(strict_types=1);

namespace App\Consts;

class ErrorCode
{
    // 无权限
    public const WITHOUT_PERMISSION = 403;

    // 未认证
    public const UNAUTHORIZED = 401;

    /**
     * openapi.
     */
    public const OPENAPI_UNAUTHORIZED = 401;
}
