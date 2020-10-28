<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\HttpServer\Contract\RequestInterface;
use stdClass;

class Response
{
    /**
     * 响应json.
     *
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    public static function json($code = 0, $msg = 'success', $data = [], array $extend = [])
    {
        $extend['code'] = (int) $code;
        $extend['msg'] = $msg;
        $extend['data'] = $data ?: new stdClass();

        return $extend;
    }

    /**
     * 收敛域名兼容跳转.
     *
     * @param string $target 要跳转的路径
     * @return string 返回要跳转路径
     */
    public static function redirectMergeDomain(RequestInterface $request, $target)
    {
        // 域名收敛来源域名
        if ($xesDomain = $request->getHeaderLine('xes-domain')) {
            // 仅处理host为域名的情况
            $domainParts = explode('.', $request->getUri()->getHost());
            if (count($domainParts) >= 3) {
                // 踢掉后面两部分
                array_pop($domainParts);
                array_pop($domainParts);
                // 拼接跳转路径
                $target = "https://{$xesDomain}/" . implode('/', $domainParts) . $target;
            }
        }

        return $target;
    }
}
