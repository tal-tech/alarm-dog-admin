<?php

declare(strict_types=1);

namespace App\Middleware;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $configer = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $response = Context::get(ResponseInterface::class);

        // 关闭跨域
        if ($configer->get('cors.enable')) {
            // 域名收敛由网关统一设置跨域、为了避免冲突，不再设置origin
            if ($request->getHeaderLine('xes-domain')) {
                $response = $response->withHeader('Access-Control-Allow-Origin', '*');
            } else {
                $configAllowOrigin = $configer->get('cors.allowOrigin', '*');
                $allowOrigin = $configAllowOrigin === true ? (
                    $request->getHeaderLine('origin') ?: '*'
                ) : $configAllowOrigin;
                $response = $response->withHeader('Access-Control-Allow-Origin', $allowOrigin);
            }

            // 其他情况
            $allowMethods = $configer->get('cors.allowMethods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response = $response->withHeader('Access-Control-Allow-Methods', $allowMethods)
                ->withHeader(
                    'Access-Control-Allow-Credentials',
                    $configer->get(
                        'cors.allowCredentials',
                        false
                    ) ? 'true' : 'false'
                )->withHeader('Access-Control-Allow-Headers', $configer->get('cors.allowHeaders', '*,Authorization'))
                ->withHeader('Access-Control-Expose-Headers', $configer->get('cors.exposeHeaders', '*,Authorization'))
                ->withHeader('Access-Control-Max-Age', $configer->get('cors.controlMaxAge', 86400));

            Context::set(ResponseInterface::class, $response);
        }

        if ($request->getMethod() == 'OPTIONS') {
            return $response;
        }

        return $handler->handle($request);
    }
}
