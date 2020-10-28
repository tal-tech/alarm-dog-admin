<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionHandler
{
    /**
     * 记录异常日志.
     */
    public static function logException(Throwable $e)
    {
        /** @var LoggerInterface */
        $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get();
        $logger->error(sprintf('%s[%s] in %s', $e->getMessage(), $e->getLine(), $e->getFile()));
        $logger->error($e->getTraceAsString());
    }
}
