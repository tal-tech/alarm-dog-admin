<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;
use App\Service\Monitor\DateTime;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Hyperf\Elasticsearch\ClientBuilderFactory;
use Hyperf\Logger\LoggerFactory;

class ElasticSearch extends DataSourceAbstract
{
    /**
     * @var int
     */
    public static $type = MonitorDatasource::TYPE_ES;

    /**
     * @var string
     */
    public static $name = 'ElasticSearch';

    /**
     * 支持索引占位符.
     *
     * @var array
     */
    public static $supportedIndexVars = [
        'yyyy', 'MM', 'dd', 'HH', 'mm', 'ss',
    ];

    /**
     * @var Client
     */
    protected $esClient;

    /**
     * 验证连接配置格式化.
     *
     * @param arry $config ['nodes' => [['host' => '', 'port' => 9200]], 'index' => 'xxx-{yyyy}']
     * @return array ['nodes' => [['host' => '', 'port' => 9200]], 'index' => '', 'index_vars' => ['yyyy']]
     */
    public function validConfig()
    {
        $respConf = [];

        // nodes字段校验
        if (empty($this->config['nodes']) || ! is_array($this->config['nodes'])) {
            throw new AppException('field `nodes` must be array in ElasticSearch config');
        }
        $nodes = [];
        foreach ($this->config['nodes'] as $node) {
            if (! is_numeric($node['port'])) {
                throw new AppException('the one of the node`s port is not numeric in ElasticSearch config', [
                    'port' => $node['port'],
                ]);
            }
            if (
                ! filter_var($node['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) &&
                ! filter_var($node['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ) {
                throw new AppException('the one of the node`s host is not ip or domain in ElasticSearch config', [
                    'host' => $node['host'],
                ]);
            }
            $nodes[] = [
                'host' => $node['host'],
                'port' => (int) $node['port'],
            ];
        }
        $respConf['nodes'] = $nodes;

        // index字段校验
        if (empty($this->config['index']) || ! is_string($this->config['index'])) {
            throw new AppException('field `index` is required in ElasticSearch config');
        }
        // 解析索引模板变量
        $indexVars = [];
        if (preg_match_all('/\{([^\{\}]+)\}/', $this->config['index'], $matches)) {
            if ($diffVars = array_diff($matches[1], self::$supportedIndexVars)) {
                $message = sprintf('not support index vars [%s] in ElasticSearch config', implode(', ', $diffVars));
                throw new AppException($message, [
                    'vars' => $matches[1],
                    'diff_vars' => $diffVars,
                ]);
            }

            $indexVars = $matches[1];
        }
        $respConf['index'] = $this->config['index'];
        $respConf['index_vars'] = $indexVars;

        $this->config = $respConf;

        return $this->config;
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect()
    {
        $this->pingEveryNode();

        $this->connect();

        $index = $this->renderIndex();
        try {
            // 获取一条数据验证索引并保持字段
            $resp = $this->esClient->search([
                'index' => $index,
                'size' => 1,
            ]);
        } catch (Missing404Exception $e) {
            throw new AppException("index [{$index}] not found in ElasticSearch", [
                'index' => $index,
            ], $e, 404);
        }

        /*
         * 判断数据是否合法
         */
        if (empty($resp['hits']) || empty($resp['hits']['hits'])) {
            throw new AppException('response data invalid in ElasticSearch', [
                'response' => $resp,
            ]);
        }

        if (empty($resp['hits']['hits'][0]) || empty($resp['hits']['hits'][0]['_source'])) {
            throw new AppException('sample data is empty, cannot validate fields in ElasticSearch');
        }

        $this->sampleData = $resp['hits']['hits'][0]['_source'];
    }

    /**
     * 连接.
     */
    public function connect()
    {
        if (! is_null($this->esClient)) {
            return;
        }

        $builder = $this->container->get(ClientBuilderFactory::class)->create();
        $hosts = array_map(function ($node) {
            return sprintf('http://%s:%s', $node['host'], $node['port']);
        }, $this->config['nodes']);

        $this->esClient = $this->createClient($builder, $hosts);
    }

    public function getFilterShoulds(array $filter)
    {
        $should = [];
        if (! $filter['conditions']) {
            return $should;
        }

        foreach ($filter['conditions'] as $condItem) {
            if (empty($condItem['rule'])) {
                continue;
            }
            $must = [];
            $mustNot = [];
            foreach ($condItem['rule'] as $rule) {
                switch ($rule['operator']) {
                    case 'eq':
                        $must[] = [
                            'term' => [
                                $rule['field'] => $rule['threshold'],
                            ],
                        ];
                        break;
                    case 'neq':
                        $mustNot[] = [
                            'term' => [
                                $rule['field'] => $rule['threshold'],
                            ],
                        ];
                        break;
                    case 'lt':
                    case 'gt':
                    case 'lte':
                    case 'gte':
                        $must[] = [
                            'range' => [
                                $rule['field'] => [
                                    $rule['operator'] => $rule['threshold'],
                                ],
                            ],
                        ];
                        break;
                    case 'in':
                        $must[] = [
                            'terms' => [
                                // 此时 threshold 应为数组
                                $rule['field'] = $rule['threshold'],
                            ],
                        ];
                        break;
                    case 'not-in':
                        $mustNot[] = [
                            'terms' => [
                                // 此时 threshold 应为数组
                                $rule['field'] = $rule['threshold'],
                            ],
                        ];
                        break;
                    default:
                        // ignore not support operator
                        break;
                }
            }
            if (! empty($must) || ! empty($mustNot)) {
                $should[] = [
                    'bool' => [
                        'must' => $must,
                        'must_not' => $mustNot,
                    ],
                ];
            }
        }

        return $should;
    }

    /**
     * 验证过滤条件.
     */
    public function validFilter(array $param)
    {
        $this->connect();

        $index = $this->renderIndex();
        $should = $this->getFilterShoulds($param['config']['filter']);
        $aggs = $this->getAggExpression($param['alarm_condition']);
        $time = DateTime::timePointLocation(time(), $param['agg_cycle']);
        $whereStartTime = DateTime::timestampToTime($time - $param['agg_cycle'], $this->tsUnit);
        $whereEndTime = DateTime::timestampToTime($time, $this->tsUnit);

        try {
            $resp = $this->esClient->search([
                'index' => $index,
                'size' => 0,
                'body' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'bool' => [
                                                'should' => $should,
                                            ],
                                        ],
                                        [
                                            'range' => [
                                                $this->tsField => [
                                                    'gte' => $whereStartTime,
                                                    'lte' => $whereEndTime,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'aggs' => $aggs,
                ],
            ]);
        } catch (Missing404Exception $e) {
            throw new AppException("index [{$index}] not found in ElasticSearch", [
                'index' => $index,
            ], $e, 404);
        }

        /*
         * 判断数据是否合法
         */
        if (empty($resp['hits']) || ! isset($resp['hits']['total'])) {
            throw new AppException('response data invalid in ElasticSearch', [
                'response' => $resp,
            ]);
        }

        if ($resp['hits']['total'] == 0) {
            throw new AppException('response data list is empty, cannot validate filter condition in ElasticSearch');
        }
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

        $index = $this->renderIndex();
        $should = $this->getFilterShoulds($param['config']['filter']);
        $aggs = $this->getAggExpression($param['alarm_condition']);
        $fieldMap = $this->fieldTypeMap($fieldConfig, array_keys($aggs));

        $whereStartTime = DateTime::timestampToTime($startTime, $this->tsUnit);
        $whereEndTime = DateTime::timestampToTime($endTime, $this->tsUnit);
        // ISO时间字符串使用date_histogram
        if ($this->tsUnit == DateTime::UNIT_ISOSTR) {
            $lists = [
                'date_histogram' => [
                    'field' => $this->tsField,
                    'interval' => "{$cycle}s",
                ],
                'aggs' => $aggs,
            ];
        } else {
            switch ($this->tsUnit) {
                case DateTime::UNIT_MS:
                    $curCycle = $cycle * 1000;
                    break;
                case DateTime::UNIT_US:
                    $curCycle = $cycle * 1000000;
                    break;
                default:
                    $curCycle = (int) $cycle;
                    break;
            }
            $lists = [
                'histogram' => [
                    'field' => $this->tsField,
                    'interval' => $curCycle,
                    'extended_bounds' => [
                        'min' => $whereStartTime,
                        'max' => $whereEndTime,
                    ],
                ],
                'aggs' => $aggs,
            ];
        }

        // 查询
        try {
            // 获取一条数据验证索引并保持字段
            $resp = $this->esClient->search([
                'index' => $index,
                'size' => 0,
                'body' => [
                    'query' => [
                        'bool' => [
                            'filter' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'bool' => [
                                                'should' => $should,
                                            ],
                                        ],
                                        [
                                            'range' => [
                                                $this->tsField => [
                                                    'gte' => $whereStartTime,
                                                    'lte' => $whereEndTime,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'aggs' => [
                        'lists' => $lists,
                    ],
                ],
            ]);
        } catch (Missing404Exception $e) {
            throw new AppException("index [{$index}] not found in ElasticSearch", [
                'index' => $index,
            ], $e, 404);
        }

        // 转换
        $data = [];
        foreach ($resp['aggregations']['lists']['buckets'] as $listBucket) {
            if ($this->tsUnit == DateTime::UNIT_ISOSTR) {
                $timestamp = DateTime::timeToTimestamp($listBucket['key_as_string'], $this->tsUnit);
            } else {
                $timestamp = DateTime::timeToTimestamp($listBucket['key'], $this->tsUnit);
            }
            // 因ES的histogram是计算周期开始的时间，而我们想要的是周期结束的时间，所以需要+一个周期
            $pointedTs = DateTime::timePointLocation($timestamp + $cycle, $cycle);
            $item = [
                'timestamp' => $pointedTs,
                'fields' => [
                    '__timestamp' => DateTime::timestampToTime($timestamp + $cycle, $this->tsUnit),
                ],
            ];

            // 将其他字段的值存储在fields中
            foreach (array_keys($aggs) as $field) {
                $formatter = MonitorDatasource::$fieldsTypeFormatters[$fieldMap[$field]];
                $item['fields'][$field] = call_user_func($formatter, $listBucket[$field]['value']);
            }

            $data[] = $item;
        }

        return $data;
    }

    /**
     * ping每个node.
     */
    protected function pingEveryNode()
    {
        foreach ($this->config['nodes'] as $node) {
            $host = sprintf('http://%s:%s', $node['host'], $node['port']);
            $builder = $this->container->get(ClientBuilderFactory::class)->create();
            $client = $this->createClient($builder, [$host]);

            if (! $client->ping()) {
                throw new AppException("node '{$host}' cannot connect in ElasticSearch", [
                    'host' => $host,
                ]);
            }
        }
    }

    /**
     * Create Client.
     *
     * @return Client
     */
    protected function createClient(ClientBuilder $builder, array $hosts)
    {
        $clientBuilder = $builder->setHosts($hosts);
        if (config('datasource.es.enable_log')) {
            $clientBuilder->setLogger($this->container->get(LoggerFactory::class)->get('elasticsearch'));
        }
        return $clientBuilder->build();
    }

    /**
     * 渲染索引.
     *
     * @return string
     */
    protected function renderIndex()
    {
        $vars = [
            'yyyy' => date('Y'),
            'MM' => date('m'),
            'dd' => date('d'),
            'HH' => date('H'),
            'mm' => date('i'),
            'ss' => date('s'),
        ];

        $replaceSearch = [];
        $replaceVars = [];
        foreach ($this->config['index_vars'] as $varName) {
            $replaceSearch[] = '{' . $varName . '}';
            $replaceVars[] = $vars[$varName];
        }

        return str_replace($replaceSearch, $replaceVars, $this->config['index']);
    }

    /**
     * 获取聚合的表达式.
     * @param mixed $param
     */
    protected function getAggExpression($param)
    {
        $aggs = [];
        foreach ($param['conditions'] as $condItem) {
            foreach ($condItem['rule'] as $rule) {
                switch ($rule['agg_method']) {
                    case 'avg':
                    case 'max':
                    case 'min':
                    case 'sum':
                        $aggs[$rule['field']] = [
                            $rule['agg_method'] => [
                                'field' => $rule['field'],
                            ],
                        ];
                        break;
                    case 'count':
                        $aggs[$rule['field']] = [
                            'value_count' => [
                                'field' => $rule['field'],
                            ],
                        ];
                        break;
                    default:
                        // ignore not support agg method
                }
            }
        }

        return $aggs;
    }
}
