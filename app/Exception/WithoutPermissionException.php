<?php

declare(strict_types=1);

namespace App\Exception;

use App\Consts\ErrorCode;
use Hyperf\Server\Exception\ServerException;

class WithoutPermissionException extends ServerException
{
    public function __construct($message = 'You don`t have permission')
    {
        parent::__construct($message, ErrorCode::WITHOUT_PERMISSION, $this);
    }
}
