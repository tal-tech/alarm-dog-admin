<?php

declare(strict_types=1);

namespace App\Service\Monitor\DataSource;

use App\Exception\AppException;
use App\Model\MonitorDatasource;
use App\Service\Monitor\DateTime;
use Hyperf\Database\Connectors\ConnectionFactory;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\MySqlConnection;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Throwable;

class MySQL extends DataSourceAbstract
{
    /**
     * @var int
     */
    public static $type = MonitorDatasource::TYPE_MYSQL;

    /**
     * @var string
     */
    public static $name = 'MySQL';

    /**
     * @var MySqlConnection
     */
    protected $connection;

    /**
     * 过滤条件操作符映射符号.
     */
    protected static $mapOpeartors = [
        'eq' => '=',
        'neq' => '<>',
        'lt' => '<',
        'gt' => '>',
        'lte' => '<=',
        'gte' => '>=',
        'in' => 'in',
        'not-in' => 'not in',
    ];

    /**
     * 验证连接配置格式化.
     *
     * @param arry $config ['host' => '', 'port' => 3306, 'database' => '', 'table' => '', 'username' => '', 'password' => '']
     * @return array ['host' => '', 'port' => 3306, 'database' => '', 'table' => '', 'username' => '', 'password' => '']
     */
    public function validConfig()
    {
        $respConf = [];

        // host, port
        if (empty($this->config['host'])) {
            throw new AppException('field `host` is required in MySQL config');
        }
        if (
            ! filter_var($this->config['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) &&
            ! filter_var($this->config['host'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        ) {
            throw new AppException('the host is not ip or domain in MySQL config', [
                'host' => $this->config['host'],
            ]);
        }
        if (empty($this->config['port'])) {
            throw new AppException('field `port` is required in MySQL config');
        }
        if (! is_numeric($this->config['port'])) {
            throw new AppException('the port is not numeric in MySQL config', [
                'port' => $this->config['port'],
            ]);
        }
        $respConf['host'] = $this->config['host'];
        $respConf['port'] = $this->config['port'];

        // 库名、表名
        if (empty($this->config['database']) || ! is_string($this->config['database'])) {
            throw new AppException('field `database` is required in MySQL config');
        }
        if (empty($this->config['table']) || ! is_string($this->config['table'])) {
            throw new AppException('field `table` is required in MySQL config');
        }
        $respConf['database'] = $this->config['database'];
        $respConf['table'] = $this->config['table'];

        // 用户名、密码，如果全部为空，则默认为哮天犬用户名、密码，否则需都填写
        if (empty($this->config['username']) && empty($this->config['password'])) {
            $respConf['username'] = null;
            $respConf['password'] = null;
        } elseif (! empty($this->config['username']) && ! empty($this->config['password'])) {
            if (! is_string($this->config['username'])) {
                throw new AppException('field `username` must be string in MySQL config', [
                    'username' => $this->config['username'],
                ]);
            }
            if (! is_string($this->config['password'])) {
                throw new AppException('field `password` must be string in MySQL config', [
                    'password' => $this->config['password'],
                ]);
            }
            $respConf['username'] = $this->config['username'];
            $respConf['password'] = $this->config['password'];
        } else {
            throw new AppException('field `username` and `password` must be all empty or not empty in MySQL config');
        }

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
            // 获取一条数据验证索引并保持字段
            $resp = $this->connection->table($this->config['table'])->first();
        } catch (QueryException $e) {
            $code = $e->getCode();
            if ($code == '42S02') {
                // 表不存在
                throw new AppException("table [{$this->config['table']}] not exists in MySQL", [
                    'table' => $this->config['table'],
                ], $e, $code);
            }
            if ($code == 1049) {
                // 库不存在
                throw new AppException("database [{$this->config['database']}] not exists in MySQL", [
                    'database' => $this->config['database'],
                ], $e, $code);
            }
            if ($code == 2002) {
                // 连接的host、port信息错误
                throw new AppException('connecion refused in MySQL', [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                ], $e, $code);
            }
            if ($code == 1045) {
                // 帐号密码错误
                throw new AppException('access denied for current user and password in MySQL', [], $e, $code);
            }
            throw $e;
        }

        if (empty($resp)) {
            throw new AppException('sample data is empty, cannot validate fields in MySQL');
        }

        $this->sampleData = get_object_vars($resp);
    }

    /**
     * 连接.
     */
    public function connect()
    {
        if (! is_null($this->connection)) {
            return;
        }

        $config = [
            'driver' => 'mysql',
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'prefix' => '',
            'database' => $this->config['database'],
            'username' => $this->config['username'] ?: config('databases.default.read.username'),
            'password' => $this->config['password'] ?: config('databases.default.read.password'),
            'charset' => 'utf8',
        ];
        $this->connection = $this->container->get(ConnectionFactory::class)->make($config);
    }

    /**
     * 过滤条件.
     */
    public function withFilter(array $filter, Builder $builder)
    {
        if (! $filter['conditions']) {
            return;
        }

        foreach ($filter['conditions'] as $condItem) {
            if (empty($condItem['rule'])) {
                continue;
            }
            $builder->where(function ($queryOr) use ($condItem) {
                $wheres = [];
                foreach ($condItem['rule'] as $rule) {
                    $wheres[] = [$rule['field'], self::$mapOpeartors[$rule['operator']], $rule['threshold']];
                }
                $queryOr->orWhere($wheres);
            });
        }
    }

    /**
     * 验证过滤条件.
     */
    public function validFilter(array $param)
    {
        $this->connect();

        [$aggs, $fields] = $this->getAggExpression($param['alarm_condition']);
        $time = DateTime::timePointLocation(time(), $param['agg_cycle']);
        $whereStartTime = DateTime::timestampToTime($time - $param['agg_cycle'], $this->tsUnit);
        $whereEndTime = DateTime::timestampToTime($time, $this->tsUnit);

        $builder = $this->connection->table($this->config['table'])
            ->where($this->tsField, '>=', $whereStartTime)
            ->where($this->tsField, '<', $whereEndTime);
        $this->withFilter($param['config']['filter'], $builder);

        try {
            $builder->select(...$aggs)->first();
        } catch (Throwable $e) {
            throw new AppException('invalid filter or alarm condition config in MySQl', [
                'filter' => $param['config']['filter'],
                'alarm_condition' => $param['alarm_condition'],
            ], $e);
        }
    }

    /**
     * 获取聚合的表达式.
     * @param mixed $param
     */
    public function getAggExpression($param)
    {
        $fields = [];
        foreach ($param['conditions'] as $condItem) {
            foreach ($condItem['rule'] as $rule) {
                switch ($rule['agg_method']) {
                    case 'avg':
                    case 'max':
                    case 'min':
                    case 'sum':
                    case 'count':
                        $aggMethod = strtoupper($rule['agg_method']);
                        $fields[$rule['field']] = Db::raw("{$aggMethod}(`{$rule['field']}`) AS `agg_{$rule['field']}`");
                        break;
                    default:
                        // ignore not support agg method
                }
            }
        }

        // 前者为要聚合的字段表达式，后者为要聚合的字段名称
        return [array_values($fields), array_keys($fields)];
    }

    /**
     * 获取聚合周期数据.
     *
     * @param array $param 监控任务配置
     * @param array $fieldConfig 数据源有关字段的配置
     * @param int $startTime 开始时间，秒级时间戳
     * @param int $endTime 结束数据，秒级时间戳
     * @param int $cycle 周期，秒
     * @return array
     */
    public function getAggDatas(array $param, array $fieldConfig, $startTime, $endTime, $cycle)
    {
        $this->connect();
        [$aggs, $fields] = $this->getAggExpression($param['alarm_condition']);
        $fieldMap = $this->fieldTypeMap($fieldConfig, $fields);

        $data = [];
        for ($time = $startTime; $time < $endTime; $time += $cycle) {
            $whereStartTime = DateTime::timestampToTime($time, $this->tsUnit);
            $whereEndTime = DateTime::timestampToTime($time + $cycle, $this->tsUnit);

            $builder = $this->connection->table($this->config['table'])
                ->where($this->tsField, '>=', $whereStartTime)
                ->where($this->tsField, '<', $whereEndTime);

            // DEBUG
            // $builder = $this->connection->table($this->config['table']);

            $this->withFilter($param['config']['filter'], $builder);

            $result = $builder->select(...$aggs)->first();
            // 将stdClass转为数组
            if (is_object($result)) {
                $result = get_object_vars($result);
            }

            $item = [
                'timestamp' => DateTime::timePointLocation($time + $cycle, $cycle),
                'fields' => [],
            ];

            // 将其他字段的值存储在fields中
            foreach ($fields as $field) {
                $formatter = MonitorDatasource::$fieldsTypeFormatters[$fieldMap[$field]];
                $item['fields'][$field] = call_user_func($formatter, $result["agg_{$field}"]);
            }

            $data[] = $item;
        }

        return $data;
    }
}
