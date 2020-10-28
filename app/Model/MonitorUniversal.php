<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Service\Monitor\DataSource\DataSourceFactory;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class MonitorUniversal extends Model
{
    /**
     * 任务状态
     */
    public const STATUS_STARTING = 1;

    public const STATUS_STARTED = 2;

    public const STATUS_STOPPING = 3;

    public const STATUS_STOPPED = 4;

    public const STATUS_EDITED = 6;

    public $timestamps = false;

    public static $statuses = [
        self::STATUS_STARTING => '启动中',
        self::STATUS_STARTED => '已启动',
        self::STATUS_STOPPING => '停止中',
        self::STATUS_STOPPED => '已停止',
        self::STATUS_EDITED => '已编辑',
    ];

    /**
     * 过滤条件操作符.
     */
    public static $filterCondOperators = [
        'eq' => '等于',
        'neq' => '不等于',
        'lt' => '小于',
        'gt' => '大于',
        'lte' => '不大于',
        'gte' => '不小于',
        'in' => '在范围内',
        'not-in' => '不在范围内',
    ];

    /**
     * 告警条件-聚合方式.
     */
    public static $alarmAggMethods = [
        'avg' => '平均值',
        'max' => '最大值',
        'min' => '最小值',
        'sum' => '求和',
        'count' => '计数',
        'last' => '最后一条记录',
    ];

    /**
     * 告警条件-条件操作符.
     */
    public static $alarmCondOperators = [
        'eq' => '等于',
        'neq' => '不等于',
        'lt' => '小于',
        'gt' => '大于',
        'lte' => '不大于',
        'gte' => '不小于',
        'in' => '在范围内',
        'not-in' => '不在范围内',
    ];

    /**
     * 拆分阈值的符号.
     */
    public static $explodeThresholdSymbol = '|';

    /**
     * 需要拆分为数组的阈值操作符.
     */
    public static $explodeThresholdOperators = [
        'in', 'not-in',
    ];

    /**
     * 支持的聚合周期
     */
    public static $supportedAggCycles = [
        1 * 60, 2 * 60, 3 * 60, 5 * 60, 10 * 60, 15 * 60, 30 * 60, 60 * 60,
    ];

    protected $table = 'monitor_universal';

    protected $fillable = [
        'task_id', 'name', 'pinyin', 'remark', 'token', 'datasource_id', 'agg_cycle', 'config',
        'alarm_condition', 'status', 'created_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'config' => 'array',
        'alarm_condition' => 'array',
    ];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * @Inject
     * @var AlarmTask
     */
    protected $alarmTask;

    /**
     * @Inject
     * @var MonitorDatasource
     */
    protected $datasource;

    /**
     * 是否存在该名称的任务
     *
     * @param string $name
     * @param int $excludeId
     * @return int
     */
    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 判断是否存在，不存在则报错.
     *
     * @param int $taskId
     * @return self
     */
    public function getByIdAndThrow($taskId)
    {
        $task = $this->where('id', $taskId)->first();
        if (empty($task)) {
            throw new AppException("task [{$taskId}] not found", [
                'task_id' => $taskId,
            ]);
        }

        return $task;
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')
            ->select('uid', 'username', 'user', 'email', 'department');
    }

    public function datasource()
    {
        return $this->hasOne(MonitorDatasource::class, 'id', 'datasource_id')
            ->select('id', 'name', 'type', 'config', 'remark');
    }

    /**
     * 关联的告警任务
     */
    public function task()
    {
        return $this->hasOne(AlarmTask::class, 'id', 'task_id')
            ->select('id', 'name', 'department_id');
    }

    /**
     * 列表.
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     * @param null|mixed $datasourceId
     * @param null|mixed $status
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list(
        $departmentId = null,
        $taskId = null,
        $datasourceId = null,
        $status = null,
        $page = 1,
        $pageSize = 20,
        $search = null,
        $order = []
    ) {
        $builder = $this->with('creator')->with('task')->with('task.department')->with('datasource')
            ->select(
                'id',
                'name',
                'remark',
                'status',
                'created_at',
                'updated_at',
                'created_by',
                'task_id',
                'datasource_id'
            );

        if ($taskId) {
            $builder->where('task_id', $taskId);
        }
        if ($datasourceId) {
            $builder->where('datasource_id', $datasourceId);
        }
        if ($departmentId && ! $taskId) {
            $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        }
        if ($status) {
            $builder->where('status', $status);
        }

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 详情.
     * @param mixed $taskId
     */
    public function showTask($taskId)
    {
        $task = $this->getByIdAndThrow($taskId);
        $task->load('creator')
            ->load('task')
            ->load('task.department')
            ->load('datasource');

        $task->setAttribute('alarm_condition', [
            'conditions' => $this->fmtConditionsForShow($task['alarm_condition']['conditions']),
        ]);
        $config = $task['config'];
        $config['filter']['conditions'] = $this->fmtConditionsForShow($config['filter']['conditions']);
        $task->setAttribute('config', $config);

        return $task;
    }

    /**
     * 删除.
     * @param mixed $taskId
     */
    public function deleteTask($taskId, User $user)
    {
        $task = $this->getByIdAndThrow($taskId);

        // 删除关联的监控记录
        MonitorRecord::secureDelete(
            $task['datasource_id'],
            $task['id'],
            MonitorRecord::TYPE_UNIVERSAL
        );

        $task->delete();
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'name', 'status');

        if ($search) {
            $builder->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%")
                    ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }

        return $builder->get();
    }

    /**
     * 创建.
     * @param mixed $param
     */
    public function storeTask($param, User $user)
    {
        // 重名判断
        if ($this->hasByName($param['name'])) {
            throw new AppException("universal monitor task [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
            ]);
        }

        $alarmTask = $this->alarmTask->getById($param['task_id'], true);
        $datasource = $this->datasource->getByIdAndThrow($param['datasource_id']);

        // 验证数组类型是否正确
        $param['config'] = $this->validAndFmtConfig($param['config']);
        $param['alarm_condition'] = $this->validAndFmtAlarmCondition($param['alarm_condition']);

        $datasourceError = [];
        try {
            // 验证过滤条件、告警字段是否合法
            $this->validTaskAttr($param);
        } catch (Throwable $e) {
            $datasourceError = [
                'errno' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        $data = [
            'task_id' => $param['task_id'],
            'name' => $param['name'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'remark' => $param['remark'],
            'token' => sha1(uniqid()),
            'datasource_id' => $param['datasource_id'],
            'agg_cycle' => $param['agg_cycle'],
            'config' => $param['config'],
            'alarm_condition' => $param['alarm_condition'],
            'status' => self::STATUS_STARTING,
            'started_at' => 0,
            'created_by' => $user['uid'],
            'created_at' => time(),
            'updated_at' => time(),
        ];

        $task = self::create($data);
        $task->load('creator');

        // 自动创建influxDB表
        // TODO

        return [$task, $datasourceError];
    }

    /**
     * 更新.
     * @param mixed $taskId
     * @param mixed $param
     * @param mixed $user
     */
    public function updateTask($taskId, $param, $user)
    {
        // 重名判断
        if ($this->hasByName($param['name'], $taskId)) {
            throw new AppException("universal monitor task [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
                'exclude_id' => $taskId,
            ]);
        }

        $task = $this->getByIdAndThrow($taskId);

        $alarmTask = $this->alarmTask->getById($param['task_id'], true);
        $datasource = $this->datasource->getByIdAndThrow($param['datasource_id']);

        // 验证数组类型是否正确
        $param['config'] = $this->validAndFmtConfig($param['config']);
        $param['alarm_condition'] = $this->validAndFmtAlarmCondition($param['alarm_condition']);

        $datasourceError = [];
        try {
            // 验证过滤条件、告警字段是否合法
            $this->validTaskAttr($param);
        } catch (Throwable $e) {
            $datasourceError = [
                'errno' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        $task['task_id'] = $param['task_id'];
        $task['name'] = $param['name'];
        $task['pinyin'] = $this->pinyin->convert($param['name']);
        $task['remark'] = $param['remark'];
        $task['datasource_id'] = $param['datasource_id'];
        $task['agg_cycle'] = $param['agg_cycle'];
        $task['config'] = $param['config'];
        $task['alarm_condition'] = $param['alarm_condition'];
        $task['updated_at'] = time();

        // 如果任务处于启动状态，则修改任务状态为EDITED
        if ($task['status'] == self::STATUS_STARTED) {
            $task['status'] = self::STATUS_EDITED;
        }

        $task->save();

        $task->load('creator');

        return [$task, $datasourceError];
    }

    /**
     * 停止任务
     * @param mixed $taskId
     */
    public function stopTask($taskId)
    {
        $task = $this->getByIdAndThrow($taskId);

        if ($task->status == static::STATUS_STOPPING) {
            throw new AppException('task was stopped, you cannot stop it repeatedly');
        }
        if ($task->status == static::STATUS_STOPPING) {
            throw new AppException('task is stopping, you cannot stop it repeatedly');
        }

        $task->status = static::STATUS_STOPPING;
        $task->save();
    }

    /**
     * 启动任务
     * @param mixed $taskId
     */
    public function startTask($taskId)
    {
        $task = $this->getByIdAndThrow($taskId);

        if ($task->status == static::STATUS_STARTED) {
            throw new AppException('task was started, you cannot start it repeatedly');
        }
        if ($task->status == static::STATUS_STARTING) {
            throw new AppException('task is starting, you cannot start it repeatedly');
        }

        $task->status = static::STATUS_STARTING;
        $task->save();
    }

    /**
     * 重置token.
     * @param mixed $taskId
     */
    public function resetToken($taskId)
    {
        $task = $this->getByIdAndThrow($taskId);

        $task->token = sha1(uniqid());
        $task->save();

        return $task;
    }

    /**
     * 验证告警过滤条件.
     * @param mixed $param
     */
    protected function validAndFmtAlarmCondition($param)
    {
        $conditions = [];

        // conditions不可以为空
        if (empty($param['conditions']) || ! is_array($param['conditions'])) {
            throw new AppException('alarm conditions cannot be empty');
        }

        // 每个字段只能使用一种聚合方式
        $fieldAggs = [];

        foreach ($param['conditions'] as $items) {
            // 告警级别
            if (! isset($items['level']) || ! is_integer($items['level'])) {
                throw new AppException('alarm condtions.*.level cannot be empty');
            }
            if (! AlarmTask::hasAlarmLevel($items['level'])) {
                throw new AppException('alarm conditions.*.level invalid');
            }

            // 持续周期
            if (empty($items['continuous_cycle']) || ! is_integer($items['continuous_cycle'])) {
                throw new AppException('alarm conditons.*.continuous_cycle cannot be empty');
            }

            // 告警条件规则
            if (empty($items['rule']) || ! is_array($items['rule'])) {
                throw new AppException('alarm conditions.*.rule connot be empty');
            }
            $rules = [
                'level' => (int) $items['level'],
                'continuous_cycle' => (int) $items['continuous_cycle'],
                'rule' => [],
            ];
            foreach ($items['rule'] as $item) {
                if (empty($item['field']) || ! is_string($item['field'])) {
                    throw new AppException('alarm conditions.*.rule.field cannot be empty');
                }

                // agg_method
                if (empty($item['agg_method']) || ! is_string($item['agg_method'])) {
                    throw new AppException('alarm conditions.*.rule.agg_method cannot be empty');
                }
                if (! isset(self::$alarmAggMethods[$item['agg_method']])) {
                    throw new AppException("alarm condition agg_method [{$item['agg_method']}] invalid", [
                        'agg_method' => $item['agg_method'],
                    ]);
                }

                // 判断该字段是否有其他聚合方式
                if (! array_key_exists($item['field'], $fieldAggs)) {
                    $fieldAggs[$item['field']] = $item['agg_method'];
                } else {
                    if ($fieldAggs[$item['field']] !== $item['agg_method']) {
                        throw new AppException('only supported to use one aggregation per key', [
                            'field' => $item['field'],
                            'prev_agg_method' => $fieldAggs[$item['field']],
                            'agg_method' => $item['agg_method'],
                        ]);
                    }
                }

                // opreator
                if (empty($item['operator']) || ! is_string($item['operator'])) {
                    throw new AppException('alarm conditions.*.rule.operator cannot be empty');
                }
                if (! isset(self::$alarmCondOperators[$item['operator']])) {
                    throw new AppException("alarm condition operator [{$item['operator']}] invalid", [
                        'operator' => $item['operator'],
                    ]);
                }

                // 此处不要用empty，可能误杀为0的值
                if (! isset($item['threshold']) || $item['threshold'] === '' || is_null($item['threshold'])) {
                    throw new AppException("alarm condition operator [{$item['operator']}]`s threshold cannot be empty");
                }
                // 如果阈值要求拆分，则拆分为数组
                if (in_array($item['operator'], self::$explodeThresholdOperators)) {
                    $threshold = array_unique(explode(self::$explodeThresholdSymbol, $item['threshold']));
                } else {
                    $threshold = $item['threshold'];
                }

                $rules['rule'][] = [
                    'field' => $item['field'],
                    'agg_method' => $item['agg_method'],
                    'operator' => $item['operator'],
                    'threshold' => $threshold,
                ];
            }
            $rules['id'] = $this->genConditionId($rules['rule']);
            $conditions[] = $rules;
        }

        return [
            'conditions' => $conditions,
        ];
    }

    /**
     * 根据条件生成ID（不一定唯一）.
     *
     * @param array $condition
     * @return int
     */
    protected function genConditionId($condition)
    {
        $rules = array_map(function ($item) {
            $threshold = is_array($item['threshold'])
                ? implode(self::$explodeThresholdSymbol, $item['threshold']) : $item['threshold'];
            return "{$item['field']}#{$item['agg_method']}#{$item['operator']}#{$threshold}";
        }, $condition);
        sort($rules);
        $str = implode('^', $rules);

        return bindec(decbin(crc32($str)));
    }

    /**
     * 验证并格式化config字段.
     *
     * @param array $param
     * @return array
     */
    protected function validAndFmtConfig($param)
    {
        return [
            'filter' => [
                'conditions' => $this->validAndFmtFilterCondition($param['filter'] ?? []),
            ],
        ];
    }

    /*1
     * 验证告警过滤条件
     *
     * @param array $param
     * @return array
     */
    protected function validAndFmtFilterCondition($param)
    {
        $conditions = [];

        // conditions可以为空
        if (empty($param['conditions']) || ! is_array($param['conditions'])) {
            return $conditions;
        }

        foreach ($param['conditions'] as $items) {
            if (empty($items['rule']) || ! is_array($items['rule'])) {
                throw new AppException('filter conditions.*.rule connot be empty');
            }
            $rules = ['rule' => []];
            foreach ($items['rule'] as $item) {
                if (empty($item['field']) || ! is_string($item['field'])) {
                    throw new AppException('filter conditions.*.rule.field cannot be empty');
                }

                if (empty($item['operator']) || ! is_string($item['operator'])) {
                    throw new AppException('filter conditions.*.rule.operator cannot be empty');
                }
                if (! isset(self::$filterCondOperators[$item['operator']])) {
                    throw new AppException("filter condition operator [{$item['operator']}] invalid", [
                        'operator' => $item['operator'],
                    ]);
                }

                // 此处不要用empty，可能误杀为0的值
                if (! isset($item['threshold']) || $item['threshold'] === '' || is_null($item['threshold'])) {
                    throw new AppException("filter condition operator [{$item['operator']}]`s threshold cannot be empty");
                }
                // 如果阈值要求拆分，则拆分为数组
                if (in_array($item['operator'], self::$explodeThresholdOperators)) {
                    $threshold = array_unique(explode(self::$explodeThresholdSymbol, $item['threshold']));
                } else {
                    $threshold = $item['threshold'];
                }

                $rules['rule'][] = [
                    'field' => $item['field'],
                    'operator' => $item['operator'],
                    'threshold' => $threshold,
                ];
            }
            $conditions[] = $rules;
        }

        return $conditions;
    }

    protected function fmtConditionsForShow($data)
    {
        $conditions = [];
        // 判断conditions为空时
        if (empty($data) || ! is_array($data)) {
            return $conditions;
        }

        foreach ($data as $items) {
            if (empty($items['rule']) || ! is_array($items['rule'])) {
                continue;
            }
            $rules = [];
            foreach ($items['rule'] as $item) {
                // 如果阈值要求拆分，则拆分为数组
                if (in_array($item['operator'], self::$explodeThresholdOperators)) {
                    $item['threshold'] = implode(self::$explodeThresholdSymbol, $item['threshold']);
                }
                $rules[] = $item;
            }
            $items['rule'] = $rules;
            $conditions[] = $items;
        }
        return $conditions;
    }

    /**
     * 验证过滤条件、告警字段是否合法.
     *
     * @param array $param
     */
    protected function validTaskAttr($param)
    {
        $dsParam = $this->datasource->getByIdAndThrow($param['datasource_id']);
        $datasource = DataSourceFactory::create($dsParam['type'], $dsParam['config'], $dsParam['timestamp_field'], $dsParam['timestamp_unit']);
        $datasource->validConfig();
        $datasource->validConnect();
        $datasource->validFilter($param);
    }
}
