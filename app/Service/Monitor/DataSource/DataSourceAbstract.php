<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;
use App\Service\Monitor\DateTime;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;

abstract class DataSourceAbstract
{
    /**
     * @var int
     */
    public static $type = 0;

    /**
     * @var string
     */
    public static $name = 'DataSourceAbstract';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * 配置信息.
     *
     * @var array
     */
    protected $config = [];

    /**
     * 时间戳字段.
     *
     * @var string
     */
    protected $tsField;

    /**
     * 时间戳单位.
     *
     * @var int
     */
    protected $tsUnit;

    /**
     * 测试的样例数据.
     *
     * @var array
     */
    protected $sampleData = [];

    public function __construct(ContainerInterface $container, array $config, string $tsField, int $tsUnit)
    {
        $this->container = $container;
        $this->config = $config;
        $this->tsField = $tsField;
        $this->tsUnit = $tsUnit;
    }

    /**
     * 验证连接配置格式化.
     */
    abstract public function validConfig();

    /**
     * 验证连接是否可用.
     */
    abstract public function validConnect();

    /**
     * 连接.
     */
    abstract public function connect();

    /**
     * 验证字段.
     */
    public function validFields(array $fields)
    {
        foreach ($fields as $item) {
            $value = Arr::get($this->sampleData, $item['field']);
            if (is_null($value)) {
                throw new AppException("field [{$item['field']}] not exists in " . static::$name, [
                    'field' => $item['field'],
                    'data' => $this->sampleData,
                ]);
            }
            MonitorDatasource::fieldTypeValidate($item['type'], $item['field'], $value);
        }
    }

    /**
     * 验证时间戳字段.
     */
    public function validTimestamp()
    {
        $value = Arr::get($this->sampleData, $this->tsField);
        if (is_null($value)) {
            throw new AppException("timestamp field [{$this->tsField}] not exists in " . static::$name, [
                'field' => $this->tsField,
                'data' => $this->sampleData,
            ]);
        }

        DateTime::isUnit($this->tsUnit, $this->tsField, $value);
    }

    /**
     * 验证过滤条件.
     */
    abstract public function validFilter(array $filter);

    /**
     * 获取聚合周期数据.
     *
     * @param array $param 监控任务配置
     * @param array $fieldConfig 数据源字段配置
     * @param int $startTime 开始时间，秒级时间戳
     * @param int $endTime 结束数据，秒级时间戳
     * @param int $cycle 周期，秒
     * @return array
     */
    abstract public function getAggDatas(array $param, array $fieldConfig, $startTime, $endTime, $cycle);

    /**
     * 获取字段类型映射.
     *
     * @param array $fieldConfig
     * @param array|bool $allowFields
     * @return array
     */
    protected function fieldTypeMap($fieldConfig, $allowFields = false)
    {
        $map = [];
        foreach ($fieldConfig['fields'] as $fieldItem) {
            if (! $allowFields || in_array($fieldItem['field'], $allowFields)) {
                $map[$fieldItem['field']] = $fieldItem['type'];
            }
        }
        // 时间戳字段强制为int类型
        if (! $allowFields || in_array($this->tsField, $allowFields)) {
            $map[$this->tsField] = MonitorDatasource::CONF_FIELDS_TYPE_INTEGER;
        }

        return $map;
    }
}
