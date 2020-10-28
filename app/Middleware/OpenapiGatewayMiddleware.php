<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Consts\ErrorCode;
use App\Model\OpenapiApp;
use App\Support\Response;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OpenapiGatewayMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $appid = (int) $this->request->getHeaderLine('x-dog-appid');
        $timestamp = (int) $this->request->getHeaderLine('x-dog-timestamp');
        $sign = $this->request->getHeaderLine('x-dog-sign');

        $app = make(OpenapiApp::class)->getByIdAndThrow($appid);
        if (empty($app)) {
            return $this->json(ErrorCode::OPENAPI_UNAUTHORIZED, "app [{$appid}] not found", 401);
        }

        // 时间戳校验
        if (abs(time() - $timestamp) > config('openapi.sign_timestamp_range')) {
            return $this->json(ErrorCode::OPENAPI_UNAUTHORIZED, 'signature was expired', 401);
        }

        // 签名校验
        if ($sign !== md5($appid . '&' . $timestamp . $app->token)) {
            return $this->json(ErrorCode::OPENAPI_UNAUTHORIZED, 'signature invalid', 401);
        }

        return $handler->handle($request);
    }

    /**
     * 响应Json.
     *
     * @param int $code
     * @param string $msg
     * @param int $statusCode
     */
    protected function json($code, $msg, $statusCode = 401)
    {
        return $this->response->json(Response::json($code, $msg))->withStatus($statusCode);
    }
}
