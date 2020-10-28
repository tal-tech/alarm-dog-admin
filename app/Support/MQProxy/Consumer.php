<?php

declare(strict_types=1);

namespace App\Support\MQProxy;

use App\Exception\AppException;
use Dog\Noticer\Component\Guzzle;
use GuzzleHttp\Client;

class Consumer
{
    /**
     * URI地址
     */
    public const CONSUME_URI_KAFKA = '/v1/kafka/fetch';

    public const COMMIT_URI_KAFKA = '/v1/kafka/commit';

    public const CONSUME_URI_RABBIT = '/v1/rabbit/fetch';

    /**
     * @var string
     */
    protected $appid;

    /**
     * @var string
     */
    protected $appkey;

    /**
     * Kafka 消费者组的名称
     * RabbitMQ 账号信息json.
     *
     * @var string
     */
    protected $group = 'dog';

    /**
     * kafka: reset策略 值为latest/earliest
     * rabbitmq: 表明是rabbitmq 值为rabbitmq.
     *
     * @var string
     */
    protected $reset = 'latest';

    /**
     * only for kafka
     * 此参数表明一次消费中最大的处理时间（提交前）
     * 服务端会在此时间之后认为消费者程序异常，从而把这部分消息自动转移给其他消费请求
     *
     * RabbitMQ只会自动ACK，不需要提交请求
     *
     * @var int
     */
    protected $commitTimeout = 60;

    /**
     * only for kafka
     * 消费有可能是重复消费的
     * 当此参数值为-1，代表无限重复消费
     * 当此参数为正整数，代表消息最大可重复消费的次数，消息将在最大重试次数之后记录日志并强制提交.
     *
     * @var int
     */
    protected $maxConsumeTimes = -1;

    /**
     * only for kafka
     * 一次消费请求中，最大返回条数.
     *
     * @var int
     */
    protected $maxMsgs = 1;

    /**
     * Guzzle客户端.
     *
     * @var Client
     */
    protected $guzzle;

    public function __construct()
    {
        $config = config('mqproxy', []);
        $this->guzzle = Guzzle::create($config['guzzle'] ?? []);
        $this->setAppid($config['appid']);
        $this->setAppkey($config['appkey']);
    }

    /**
     * @param string $appkey
     * @param mixed $appid
     * @return self
     */
    public function setAppid($appid)
    {
        $this->appid = $appid;

        return $this;
    }

    /**
     * @param string $appkey
     * @return self
     */
    public function setAppkey($appkey)
    {
        $this->appkey = $appkey;

        return $this;
    }

    /**
     * 获取Kafka消息.
     *
     * @param string $proxy
     * @param string $topic
     * @param array $options
     * @return array
     */
    public function fetchKafka($proxy, $topic, $options = [])
    {
        $json = [
            'queues' => $topic,
            'group' => $this->group,
            'reset' => $this->reset,
            'commitTimeout' => $this->commitTimeout,
            'maxConsumeTimes' => $this->maxConsumeTimes,
            'maxMsgs' => $this->maxMsgs,
        ];
        $json = array_merge($json, $options);

        $uri = $proxy . self::CONSUME_URI_KAFKA;

        return $this->sendRequest($uri, $json);
    }

    /**
     * 获取RabbitMQ消息.
     *
     * @param string $proxy
     * @param string $queues
     * @param array $options
     * @return array
     */
    public function fetchRabbit($proxy, $queues, $options = [])
    {
        $json = [
            'group' => $this->group,
            'queues' => $queues,
            'reset' => $this->reset,
            'commitTimeout' => $this->commitTimeout,
            'maxConsumeTimes' => $this->maxConsumeTimes,
            'maxMsgs' => $this->maxMsgs,
        ];
        $json = array_merge($json, $options);

        $uri = $proxy . self::CONSUME_URI_RABBIT;

        return $this->sendRequest($uri, $json);
    }

    /**
     * 提交单个offset.
     *
     * @param string $proxy
     * @param string $topic
     * @param int $partition
     * @param int $offset
     * @param string $group
     * @return array
     */
    public function commitKafka($proxy, $topic, $partition, $offset, $group = null)
    {
        $offsetItem = [
            'topic' => $topic,
            'partition' => $partition,
            'left' => $offset,
            'right' => $offset,
        ];

        return $this->commitsKafka($proxy, $topic, [$offsetItem], $group);
    }

    /**
     * 提交多个offset.
     *
     * @param string $proxy
     * @param string $topic
     * @param array $offsets
     * @param string $group
     * @return array
     */
    public function commitsKafka($proxy, $topic, $offsets, $group = null)
    {
        $json = [
            'group' => $group ?: $this->group,
            'queues' => $topic,
            'data' => $offsets,
        ];

        $uri = $proxy . self::COMMIT_URI_KAFKA;

        return $this->sendRequest($uri, $json);
    }

    /**
     * 发送请求
     *
     * @return array
     */
    protected function sendRequest(string $uri, array $json)
    {
        $resp = $this->guzzle->post($uri, [
            'json' => $json,
            'headers' => $this->genGatewayHeaders(),
        ]);

        if (($statusCode = $resp->getStatusCode()) != 200) {
            throw new AppException("response status code is not 200 but got {$statusCode} in Kafka Consumer", [
                'status_code' => $statusCode,
            ], null, $statusCode);
        }

        $body = (string) $resp->getBody()->getContents();
        $json = json_decode($body, true);
        if (! is_array($json) || ! isset($json['code'])) {
            throw new AppException("invalid response body: {$body} in Kafka Consumer", [
                'body' => $body,
            ]);
        }

        if ($json['code'] != 0) {
            $msg = $json['msg'] ?? 'unknown';
            throw new AppException("occur error: {$msg} in Kafka Consumer", [
                'msg' => $msg,
            ], null, (int) $json['code']);
        }

        return $json;
    }

    /**
     * 生成网关认证Header.
     *
     * @return array
     */
    protected function genGatewayHeaders()
    {
        $timestamp = time();

        return [
            'X-Auth-Appid' => $this->appid,
            'X-Auth-TimeStamp' => $timestamp,
            'X-Auth-Sign' => md5($this->appid . '&' . $timestamp . $this->appkey),
        ];
    }
}
