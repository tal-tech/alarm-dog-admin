<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Context\Auth;
use App\Model\User;
use App\Support\Response;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\Utils\Context;
use Phper666\JWTAuth\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class JwtAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var JWT
     */
    protected $jwt;

    public function __construct(HttpResponse $response, JWT $jwt)
    {
        $this->response = $response;
        $this->jwt = $jwt;
        $this->debug = config('app.jwt_debug');
        $this->debugUid = (int) config('app.jwt_debug_uid');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            // 判断是否开启dubug
            if ($this->debug === true) {
                // 开启全量debug，则直接全量模拟debug jwt
                return $this->respDebug($request, $handler);
            }
            if ($this->debug === 'auto' && ! $request->hasHeader('Authorization')) {
                // 开启自动debug，仅在header中不存在Authorization header时才模拟debug jwt
                return $this->respDebug($request, $handler);
            }

            if ($this->jwt->checkToken()) {
                $playload = $this->jwt->getParserData();

                $user = User::where('uid', $playload['uid'])->first();
                if (empty($user)) {
                    return $this->json(401, 'invalid token: user not exists', 401);
                }

                $auth = new Auth($user);
                Context::set(Auth::class, $auth);

                // 兼容老的JWT方案
                $request = Context::get(ServerRequestInterface::class);
                $request = $request->withAttribute('user', $user);
                Context::set(ServerRequestInterface::class, $request);
            }
        } catch (Throwable $e) {
            return $this->json(401, 'invalid token: ' . $e->getMessage(), 401);
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
    protected function json($code, $msg, $statusCode)
    {
        return $this->response->json(Response::json($code, $msg))->withStatus($statusCode);
    }

    /**
     * 响应debug.
     */
    protected function respDebug(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $user = User::where('uid', $this->debugUid)->first();
        if (! $this->debugUid || empty($user)) {
            return $this->json(401, 'invalid token: user not exists for debug user ' . $this->debugUid, 401);
        }

        $auth = new Auth($user);
        Context::set(Auth::class, $auth);

        // 兼容老的JWT方案
        $request = Context::get(ServerRequestInterface::class);
        $request = $request->withAttribute('user', $user);
        Context::set(ServerRequestInterface::class, $request);

        return $handler->handle($request);
    }
}
