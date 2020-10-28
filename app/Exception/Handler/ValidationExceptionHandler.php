<?php

declare(strict_types=1);

namespace App\Exception\Handler;

use App\Support\Response;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();
        /** @var \Hyperf\Validation\ValidationException $throwable */
        $msg = $throwable->validator->errors()->first();
        $data = json_encode(Response::json(422, $msg, $throwable->validator->errors()));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json; charset=utf-8')->withBody(new SwooleStream($data));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
