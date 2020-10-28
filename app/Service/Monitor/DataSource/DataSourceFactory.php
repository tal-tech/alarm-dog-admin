<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;

class DataSourceFactory
{
    /**
     * 工厂Map.
     *
     * @var array
     */
    protected static $factoryMap = [
        MonitorDatasource::TYPE_ES => ElasticSearch::class,
        MonitorDatasource::TYPE_KAFKA => Kafka::class,
        MonitorDatasource::TYPE_MYSQL => MySQL::class,
        MonitorDatasource::TYPE_WEBHOOK => Webhook::class,
    ];

    /**
     * 数据源工厂
     *
     * @param int $type
     */
    public static function create($type, array $config, string $tsField, int $tsUnit): DataSourceAbstract
    {
        if (! isset(static::$factoryMap[$type])) {
            throw new AppException("not support data source type [{$type}]", [
                'type' => $type,
            ]);
        }

        return make(static::$factoryMap[$type], [
            'config' => $config,
            'tsField' => $tsField,
            'tsUnit' => $tsUnit,
        ]);
    }
}
