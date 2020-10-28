<?php

declare(strict_types=1);

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    public const SERVER_ERROR = 500;

    /**
     * @Message("登录状态无效")
     */
    public const UNAUTHORIZED = 401;

    /**
     * @Message("无权限")
     */
    public const FORBIDDEN = 403;
}
