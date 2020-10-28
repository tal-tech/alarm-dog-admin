<?php

declare(strict_types=1);

namespace App\Service\Monitor\ProtocolDetect;

use App\Exception\AppException;
use App\Model\MonitorProtocolDetect;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\Coroutine;

class Http extends ProtocolDetectAbstract
{
    /**
     * @var int
     */
    public static $protocol = MonitorProtocolDetect::PROTOCOL_HTTP;

    /**
     * @var string
     */
    public static $name = 'HTTP';

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
     *                     'need_gateway_auth' => true, 'http_user' => '', 'http_password' => '']
     * @return array 同上
     */
    public function validConfig(): array
    {
        $respConf = [];

        // url
        if (empty($this->config['url']) || ! is_string($this->config['url'])) {
            throw new AppException('field `url` is required in Http config');
        }
        if (
            ! filter_var($this->config['url'], FILTER_VALIDATE_URL) ||
            ! preg_match('/^https?:\/\//', $this->config['url'])
        ) {
            throw new AppException('field `url` must be a active url in Http config', [
                'url' => $this->config['url'],
            ]);
        }
        $respConf['url'] = $this->config['url'];

        // method
        if (empty($this->config['method']) || ! is_string($this->config['method'])) {
            throw new AppException('field `method` is required in Http config');
        }
        if (! isset(MonitorProtocolDetect::$confHttpMethods[$this->config['method']])) {
            $allowMethods = implode(',', array_keys(MonitorProtocolDetect::$confHttpMethods));
            throw new AppException("field `method` must be in {$allowMethods} in Http config", [
                'method' => $this->config['method'],
                'allow_methods' => array_keys(MonitorProtocolDetect::$confHttpMethods),
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
                'type' => MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_NONE,
                'params' => [],
            ];
        } else {
            $this->validBody($this->config['body'], $respConf);
        }

        // need_gateway_auth
        $respConf['need_gateway_auth'] = ! empty($this->config['need_gateway_auth']);

        // http_user、http_password必须同时为空或者同时不为空
        if (empty($this->config['http_user']) && empty($this->config['http_password'])) {
            $respConf['http_user'] = null;
            $respConf['http_password'] = null;
        } elseif (! empty($this->config['http_user']) && ! empty($this->config['http_password'])) {
            // http_user
            if (! is_string($this->config['http_user'])) {
                throw new AppException('field `http_user` must be string in Http config', [
                    'http_user' => $this->config['http_user'],
                ]);
            }
            // http_password
            if (! is_string($this->config['http_password'])) {
                throw new AppException('field `http_password` must be string in Http config', [
                    'http_user' => $this->config['http_password'],
                ]);
            }
            $respConf['http_user'] = $this->config['http_user'];
            $respConf['http_password'] = $this->config['http_password'];
        } else {
            throw new AppException('field `http_user` and `http_password` must both be empty or not empty in Http config');
        }

        $this->config = $respConf;

        return $this->config;
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect(): array
    {
        $this->connect();

        try {
            $options = $this->buildRequestOptions();
            $resp = $this->guzzleClient->request($this->config['method'], $this->config['url'], $options);
        } catch (ConnectException $e) {
            throw new AppException("domain or port invalid: {$e->getMessage()}", [
                'error' => $e->getMessage(),
                'url' => $this->config['method'],
            ], $e, $e->getCode());
        } catch (BadResponseException $e) {
            // do nothing
            // 忽略服务端、客户端错误
            $resp = $e->getResponse();
        }

        // 状态码
        $statusCode = $resp->getStatusCode();
        $body = (string) $resp->getBody()->getContents();

        return [
            'success' => $statusCode == 200,
            'http' => [
                'status_code' => $statusCode,
                'body_sample' => mb_substr($body, 0, 200),
            ],
        ];
    }

    /**
     * 连接.
     */
    public function connect()
    {
        if (! is_null($this->guzzleClient)) {
            return;
        }

        $this->guzzleClient = $this->createGuzzleClient();
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
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_JSON:
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_X_WWW_FORM:
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_FORM_DATA:
                if (empty($config['params'])) {
                    $respConf['body']['params'] = [];
                    return;
                }
                if (! is_array($config['params'])) {
                    throw new AppException("body params must be array when body type is {$config['type']}", [
                        'params' => $config['params'],
                    ]);
                }
                if ($config['type'] == MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_JSON) {
                    $respConf['body']['params'] = $config['params'];
                } else {
                    $respConf['body']['params'] = $this->validKV('body.params', $config['params']);
                }
                break;
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_TEXT:
                if (! empty($config['content']) && ! is_string($config['content'])) {
                    throw new AppException("body content must be string when body type is {$config['type']}", [
                        'content' => $config['content'],
                    ]);
                }
                $respConf['body']['content'] = empty($config['content']) ? '' : $config['content'];
                break;
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_NONE:
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
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_X_WWW_FORM:
                $options['form_params'] = $this->config['body']['params'];
                break;
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_JSON:
                $options['json'] = $this->config['body']['params'];
                break;
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_FORM_DATA:
                $multipart = [];
                foreach ($this->config['body']['params'] as $key => $value) {
                    $multipart[] = [
                        'name' => $key,
                        'contents' => $value,
                    ];
                }
                $options['multipart'] = $multipart;
                break;
            case MonitorProtocolDetect::CONF_HTTP_BODY_TYPE_TEXT:
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

        // http basic认证
        if (! empty($this->config['http_user']) && ! empty($this->config['http_password'])) {
            $options['auth'] = [$this->config['http_user'], $this->config['http_password']];
        }

        return $options;
    }

    /**
     * 创建GuzzleHttp Client.
     */
    protected function createGuzzleClient()
    {
        $options = config('protocoldetect.http.guzzle', []);

        $handler = $this->createGuzzleHandler();
        $this->pushGuzzleMiddlewares($handler);
        $options['handler'] = $handler;

        return $this->clientFactory->create($options);
    }

    /**
     * 创建Guzzle HandlerStack.
     *
     * @return HandlerStack
     */
    protected function createGuzzleHandler()
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
    protected function pushGuzzleMiddlewares(HandlerStack $handler)
    {
        if (config('protocoldetect.http.enable_log')) {
            $format = ">>>>>>>>\n{request}\n<<<<<<<<\n{res_headers}\n--------\n{error}";
            $formatter = new MessageFormatter($format);
            $logger = $this->container->get(LoggerFactory::class)->get('http');

            $middleware = Middleware::log($logger, $formatter, 'debug');
            $handler->push($middleware, 'logger');
        }
    }
}
