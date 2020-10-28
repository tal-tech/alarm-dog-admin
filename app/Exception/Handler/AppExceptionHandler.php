<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Exception\AppException;
use App\Support\Response;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class AppExceptionHandler extends ExceptionHandler
{
    protected $logger;

    public function __construct(LoggerFactory $logger)
    {
        $this->logger = $logger->get();
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s[%s] in %s', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile()));
        $this->logger->error($throwable->getTraceAsString());

        // 拦截强制转为Json
        $code = $throwable->getCode() ?: 1;
        $msg = $throwable->getMessage() . '(' . $throwable->getCode() . ')';
        $data = [];
        if ($throwable instanceof AppException) {
            $data = $throwable->getContext();
        } elseif ($throwable instanceof ValidationException) {
            $data = [
                'errors' => $throwable->errors(),
            ];
        }

        $json = Response::json($code, $msg, $data);
        $text = json_encode($json);
        return $response->withHeader('Content-Type', 'application/json')->withBody(new SwooleStream($text));
        return $response->withStatus(500)->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
