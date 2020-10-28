<?php

declare(strict_types=1);

namespace App\Exception;

use Hyperf\Server\Exception\ServerException;
use Throwable;

class AppException extends ServerException
{
    /**
     * 上下文信息.
     *
     * @var array
     */
    protected $context = [];

    public function __construct(string $message, array $context = [], Throwable $previous = null, int $code = 0)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
