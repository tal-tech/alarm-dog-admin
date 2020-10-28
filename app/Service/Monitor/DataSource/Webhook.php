<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;

class Webhook extends DataSourceAbstract
{
    /**
     * @var int
     */
    public static $type = MonitorDatasource::TYPE_WEBHOOK;

    /**
     * @var string
     */
    public static $name = 'WEBHOOK';

    /**
     * GuzzleHttp Client.
     *
     * @var Client
     */
    protected $guzzleClient;

    /**
     * @Inject
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * 验证连接配置格式化.
     *
     * @param arry $config ['url' => '', 'method' => 'POST', 'headers' => ['Content-Type' => 'application/json'],
     *                     'query' => ['foo' => 'bar', ], 'body' => [
     *                     'type' => 'application/json', 'params' => ['foo' => 'bar'], 'content' => ''],
     *                     'need_gateway_auth' => true]
     * @return array 同上
     */
    public function validConfig()
    {
        $respConf = [];

        // url
        if (empty($this->config['url']) || ! is_string($this->config['url'])) {
            throw new AppException('field `url` is required in Webhook config');
        }
        if (
            ! filter_var($this->config['url'], FILTER_VALIDATE_URL) ||
            ! preg_match('/^https?:\/\//', $this->config['url'])
        ) {
            throw new AppException('field `url` must be a active url in Webhook config', [
                'url' => $this->config['url'],
            ]);
        }
        $respConf['url'] = $this->config['url'];

        // method
        if (empty($this->config['method']) || ! is_string($this->config['method'])) {
            throw new AppException('field `method` is required in Webhook config');
        }
        if (! isset(MonitorDatasource::$confWebhookMethods[$this->config['method']])) {
            $allowMethods = implode(',', array_keys(MonitorDatasource::$confWebhookMethods));
            throw new AppException("field `method` must be in {$allowMethods} in Webhook config", [
                'method' => $this->config['method'],
                'allow_methods' => array_keys(MonitorDatasource::$confWebhookMethods),
            ]);
        }
        $respConf['method'] = $this->config['method'];

        // headers
        $respConf['headers'] = empty($this->config['headers']) ? [] : $this->validKV('headers', $this->config['headers']);

        // query
        $respConf['query'] = empty($this->config['query']) ? [] : $this->validKV('query', $this->config['query']);

        // body
        if (empty($this->config['body']) || empty($this->config['body']['type'])) {
            $respConf['body'] = [
                'type' => MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_NONE,
                'params' => [],
            ];
        } else {
            $this->validBody($this->config['body'], $respConf);
        }

        // need_gateway_auth
        $respConf['need_gateway_auth'] = ! empty($this->config['need_gateway_auth']);

        $this->config = $respConf;

        return $this->config;
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect()
    {
        $this->connect();

        try {
            $options = $this->buildRequestOptions();
            $resp = $this->guzzleClient->request($this->config['method'], $this->config['url'], $options);

            // 如果响应的状态码不为200，则抛出异常
            if (($statusCode = $resp->getStatusCode()) != 200) {
                $body = (string) $resp->getBody()->getContents();
                $throwBody = mb_substr($body, 0, 100);
                $message = "Client error: `{$this->config['method']} {$this->config['url']}` resulted in status code" .
                    " not 200 but got `{$statusCode}` response:\n{$throwBody}";
                throw new AppException($message, [
                    'body' => $body,
                    'url' => $this->config['url'],
                    'method' => $this->config['method'],
                ], null, $statusCode);
            }
        } catch (ConnectException $e) {
            throw new AppException("domain or port invalid: {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'url' => $this->config['method'],
            ], $e, $e->getCode());
        } catch (ClientException $e) {
            throw new AppException($e->getMessage(), [], $e, $e->getCode());
        } catch (ServerException $e) {
            throw new AppException($e->getMessage(), [], $e, $e->getCode());
        }

        $body = (string) $resp->getBody()->getContents();
        if (empty($body)) {
            throw new AppException('sample data is empty, cannot validate fields in Webhook', [
                'response' => $body,
            ]);
        }
        $json = json_decode($body, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            throw new AppException("response body is invalid json, catch error: {$error}", [
                'body' => $body,
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
        if (! is_null($this->guzzleClient)) {
            return;
        }

        $this->guzzleClient = static::createGuzzleClient();
    }

    /**
     * 创建GuzzleHttp Client.
     *
     * @return Client
     */
    public static function createGuzzleClient()
    {
        $options = config('datasource.webhook.guzzle', []);

        $handler = static::createGuzzleHandler();
        static::pushGuzzleMiddlewares($handler);
        $options['handler'] = $handler;

        return ApplicationContext::getContainer()->get(ClientFactory::class)->create($options);
    }

    /**
     * 验证过滤条件.
     */
    public function validFilter(array $filter)
    {
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
    }

    /**
     * 验证key-value类型数组.
     *
     * @param string $field
     * @param array $data
     * @return array
     */
    protected function validKV($field, $data)
    {
        if (! is_array($data)) {
            throw new AppException("field `{$field}` must be array in Webhook config", [
                'field' => $field,
                'data' => $data,
            ]);
        }
        foreach ($data as $key => $value) {
            // 不允许为索引数组
            if (is_integer($key)) {
                throw new AppException("field `{$field}` not allow is index-array, must be assoc-array", [
                    'field' => $field,
                    'key' => $key,
                ]);
            }
            if (! is_scalar($value)) {
                throw new AppException("field `{$field}` item`s value must be a scalar type", [
                    'field' => $field,
                    'value' => $value,
                ]);
            }
        }

        return $data;
    }

    /**
     * 验证body部分内容.
     * @param mixed $config
     * @param mixed $respConf
     */
    protected function validBody($config, &$respConf)
    {
        $respConf['body']['type'] = $config['type'];

        switch ($config['type']) {
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_JSON:
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_X_WWW_FORM:
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_FORM_DATA:
                if (empty($config['params'])) {
                    $respConf['body']['params'] = [];
                    return;
                }
                if (! is_array($config['params'])) {
                    throw new AppException("body params must be array when body type is {$config['type']}", [
                        'params' => $config['params'],
                    ]);
                }
                if ($config['type'] == MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_JSON) {
                    $respConf['body']['params'] = $config['params'];
                } else {
                    $respConf['body']['params'] = $this->validKV('body.params', $config['params']);
                }
                break;
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_TEXT:
                if (! empty($config['content']) && ! is_string($config['content'])) {
                    throw new AppException("body content must be string when body type is {$config['type']}", [
                        'content' => $config['content'],
                    ]);
                }
                $respConf['body']['content'] = empty($config['content']) ? '' : $config['content'];
                break;
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_NONE:
                $respConf['body']['params'] = [];
                break;
            default:
                throw new AppException("not support body type [{$config['type']}]", [
                    'type' => $config['type'],
                ]);
        }
    }

    /**
     * 构建Guzzle请求的options参数.
     */
    protected function buildRequestOptions()
    {
        $options = [
            'headers' => [],
        ];

        if ($this->config['headers']) {
            $options['headers'] = $this->config['headers'];
        }
        if ($this->config['query']) {
            $options['query'] = $this->config['query'];
        }

        // body部分
        switch ($this->config['body']['type']) {
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_X_WWW_FORM:
                $options['form_params'] = $this->config['body']['params'];
                break;
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_JSON:
                $options['json'] = $this->config['body']['params'];
                break;
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_FORM_DATA:
                $multipart = [];
                foreach ($this->config['body']['params'] as $key => $value) {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
                $options['multipart'] = $multipart;
                break;
            case MonitorDatasource::CONF_WEBHOOK_BODY_TYPE_TEXT:
                $options['body'] = $this->config['body']['content'];
                break;
        }

        // 网关鉴权 need_gateway_auth
        if ($this->config['need_gateway_auth']) {
            $appid = config('datasource.webhook.gw_appid');
            $appkey = config('datasource.webhook.gw_appkey');
            $timestamp = time();
            $options['headers']['X-Auth-Appid'] = $appid;
            $options['headers']['X-Auth-TimeStamp'] = $timestamp;
            $options['headers']['X-Auth-Sign'] = md5("{$appid}&{$timestamp}{$appkey}");
        }

        return $options;
    }

    /**
     * 创建Guzzle HandlerStack.
     *
     * @return HandlerStack
     */
    protected static function createGuzzleHandler()
    {
        $handler = null;
        if (Coroutine::inCoroutine()) {
            $handler = make(CoroutineHandler::class);
        }

        return HandlerStack::create($handler);
    }

    /**
     * 创建中间件.
     */
    protected static function pushGuzzleMiddlewares(HandlerStack $handler)
    {
        if (config('datasource.webhook.enable_log')) {
            $format = ">>>>>>>>\n{request}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
            $formatter = new MessageFormatter($format);
            $logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get('webhook');

            $middleware = Middleware::log($logger, $formatter, 'debug');
            $handler->push($middleware, 'logger');
        }
    }
}
