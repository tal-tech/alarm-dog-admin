<?php

declare(strict_types=1);

namespace App\Exception;

use App\Constants\ErrorCode;
use Hyperf\Server\Exception\ServerException;
use Throwable;

class UnauthorizedException extends ServerException
{
    public function __construct(string $message = null, Throwable $previous = null)
    {
        if (is_null($message)) {
            $message = ErrorCode::getMessage(ErrorCode::UNAUTHORIZED);
        }

        parent::__construct($message, ErrorCode::UNAUTHORIZED, $previous);
    }
}
