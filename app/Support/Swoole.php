<?php

declare(strict_types=1);

namespace App\Support;

use Hyperf\Utils\ApplicationContext;
use Swoole\Server as SwServer;

class Swoole
{
    /**
     * 广播到wokers进程.
     *
     * @param string $action 广播动作
     * @param array $data 广播数据
     * @param bool $ignoreCurrent 是否忽略当前进程
     */
    public static function broadcastToWorkers(string $action, array $data = [], bool $ignoreCurrent = true)
    {
        $message = json_encode([
            'action' => $action,
            'data' => $data,
        ]);

        /**
         * @var SwServer
         */
        $server = ApplicationContext::getContainer()->get(SwServer::class);
        for ($workerId = 0; $workerId < $server->setting['worker_num']; $workerId++) {
            if (! $ignoreCurrent || $workerId != $server->worker_id) {
                $server->sendMessage($message, $workerId);
            }
        }
    }
}
