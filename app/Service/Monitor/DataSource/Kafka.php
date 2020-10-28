<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;
use App\Service\Monitor\DateTime;
use App\Support\MQProxy\Consumer;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Arr;

class Kafka extends DataSourceAbstract
{
    /**
     * @var int
     */
    public static $type = MonitorDatasource::TYPE_KAFKA;

    /**
     * @var string
     */
    public static $name = 'Kafka';

    /**
     * @Inject
     * @var Consumer
     */
    protected $consumer;

    /**
     * 验证连接配置格式化.
     *
     * @param arry $config ['consumer_proxy' => 'http://xxx', 'topic' => 'monitor-topic']
     * @return array 同上
     */
    public function validConfig()
    {
        $respConf = [];

        // consumer_proxy
        if (empty($this->config['consumer_proxy'])) {
            throw new AppException('field `consumer_proxy` is required in Kafka config');
        }
        if (! filter_var($this->config['consumer_proxy'], FILTER_VALIDATE_URL)) {
            throw new AppException('field `consumer_proxy` is not a active url in Kafka config');
        }
        // 去除结尾的 / 字符串
        $respConf['consumer_proxy'] = preg_replace('/\/+$/', '', $this->config['consumer_proxy']);

        // topic
        if (empty($this->config['topic']) || ! is_string($this->config['topic'])) {
            throw new AppException('field `topic` is required in Kafka config');
        }
        $respConf['topic'] = $this->config['topic'];

        $this->config = $respConf;

        return $this->config;
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect()
    {
        $this->connect();

        $resp = $this->consumer->fetchKafka($this->config['consumer_proxy'], $this->config['topic']);

        if (empty($resp['data']) || empty($resp['data'][0]) || empty($resp['data'][0]['payload'])) {
            throw new AppException('sample data is empty, cannot validate fields in Kafka', [
                'response' => $resp,
            ]);
        }

        $json = json_decode($resp['data'][0]['payload'], true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            throw new AppException("payload is invalid json, catch error: {$error}", [
                'payload' => $resp['data'][0]['payload'],
                'error' => $error,
            ], null, json_last_error());
        }

        $this->sampleData = $json;
    }

    /**
     * 连接.
     */
    public function connect()
    {
        // do nothing
    }

    /**
     * 验证过滤条件.
     */
    public function validFilter(array $param)
    {
        // do nothing
    }

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
    public function getAggDatas(array $param, array $fieldConfig, $startTime, $endTime, $cycle)
    {
        $this->connect();
        $fieldMap = $this->fieldTypeMap($fieldConfig, $this->getAggFields($param['alarm_condition']));

        $resp = $this->consumer->fetchKafka($this->config['consumer_proxy'], $this->config['topic'], [
            'maxMsgs' => (int) ceil(($endTime - $startTime) / $cycle),
        ]);

        if (empty($resp['data'])) {
            throw new AppException('nothing fetching successfully in Kafka', [
                'response' => $resp,
            ]);
        }

        // 数据处理
        $data = [];
        foreach ($resp['data'] as $kItem) {
            $payload = json_decode($kItem['payload'], true);
            $time = Arr::get($payload, $this->tsField);
            if (is_null($time)) {
                throw new AppException('timestamp field`value cannot be null', [
                    'timestamp_field' => $this->tsField,
                    'value' => $time,
                ]);
            }
            $timestamp = DateTime::timeToTimestamp($time, $this->tsUnit);
            $pointedTs = DateTime::timePointLocation($timestamp, $cycle);

            $item = [
                'timestamp' => $pointedTs,
                'fields' => [
                    '__timestamp' => $time,
                ],
            ];

            foreach ($fieldMap as $field => $type) {
                $value = Arr::get($payload, $field);
                if (is_null($value)) {
                    throw new AppException("the field value of [{$field}] cannot be empty", [
                        'field' => $field,
                        'payload' => $payload,
                    ]);
                }
                $formatter = MonitorDatasource::$fieldsTypeFormatters[$type];
                $item['fields'][$field] = call_user_func($formatter, $value);
            }
            $data[] = $item;
        }

        return $data;
    }

    /**
     * 获取聚合的表达式.
     * @param mixed $param
     */
    protected function getAggFields($param)
    {
        $fields = [];
        foreach ($param['conditions'] as $condItem) {
            foreach ($condItem['rule'] as $rule) {
                $fields[$rule['field']] = 1;
            }
        }

        return array_keys($fields);
    }
}
