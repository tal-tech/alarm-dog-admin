<?php

declare(strict_types=1);

namespace App\Service\Monitor\ProtocolDetect;

use App\Exception\AppException;
use App\Model\MonitorProtocolDetect;

class ProtocolDetectFactory
{
    /**
     * 工厂Map.
     *
     * @var array
     */
    protected static $factoryMap = [
        MonitorProtocolDetect::PROTOCOL_HTTP => Http::class,
        // MonitorProtocolDetect::PROTOCOL_TCP => Tcp::class,
        // MonitorProtocolDetect::PROTOCOL_UDP => Udp::class,
        // MonitorProtocolDetect::PROTOCOL_DNS => Dns::class,
    ];

    /**
     * 数据源工厂
     *
     * @param int $protocal
     */
    public static function create($protocal, array $config): ProtocolDetectAbstract
    {
        if (! isset(static::$factoryMap[$protocal])) {
            throw new AppException("not support detect protocol [{$protocal}]", [
                'protocal' => $protocal,
            ]);
        }

        return make(static::$factoryMap[$protocal], ['config' => $config]);
    }
}
