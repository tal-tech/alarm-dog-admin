<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Service\Monitor\ProtocolDetect\ProtocolDetectFactory;
use App\Service\Pinyin;
use App\Support\MySQL;
use Hyperf\Di\Annotation\Inject;

class MonitorProtocolDetect extends Model
{
    /**
     * 任务状态
     */
    public const STATUS_STARTING = 1;

    public const STATUS_STARTED = 2;

    public const STATUS_STOPPING = 3;

    public const STATUS_STOPPED = 4;

    public const STATUS_EDITED = 6;

    /**
     * 支持的协议.
     */
    public const PROTOCOL_HTTP = 1;

    public const PROTOCOL_TCP = 2;

    public const PROTOCOL_UDP = 3;

    public const PROTOCOL_DNS = 4;

    /**
     * HTTP协议-body类型.
     */
    public const CONF_HTTP_BODY_TYPE_JSON = 'application/json';

    public const CONF_HTTP_BODY_TYPE_TEXT = 'text/plain';

    public const CONF_HTTP_BODY_TYPE_X_WWW_FORM = 'application/x-www-form-urlencoded';

    public const CONF_HTTP_BODY_TYPE_FORM_DATA = 'multipart/form-data';

    public const CONF_HTTP_BODY_TYPE_NONE = 'none';

    /**
     * DNS协议-类型.
     */
    public const CONF_DNS_A = 'A';

    public const CONF_DNS_MX = 'MX';

    public const CONF_DNS_CNAME = 'CNAME';

    public $timestamps = false;

    public static $statuses = [
        self::STATUS_STARTING => '启动中',
        self::STATUS_STARTED => '已启动',
        self::STATUS_STOPPING => '停止中',
        self::STATUS_STOPPED => '已停止',
        self::STATUS_EDITED => '已编辑',
    ];

    public static $protocols = [
        self::PROTOCOL_HTTP => 'HTTP',
        // self::PROTOCOL_TCP => 'TCP',
        // self::PROTOCOL_UDP => 'UDP',
        // self::PROTOCOL_DNS => 'DNS',
    ];

    /**
     * 不同协议支持的字段.
     */
    public static $protocolAlarmFields = [
        self::PROTOCOL_HTTP => [
            'http.status' => '状态码',
            'http.headers' => '响应头',
            'http.body' => '响应内容',
            'http.body_length' => 'body长度',
            'http.body_json' => '响应json',
            'http.request_time' => '响应时间', // 毫秒
        ],
        self::PROTOCOL_TCP => [
            'tcp.body' => '响应内容',
            'tcp.body_length' => 'body长度',
            'tcp.body_json' => '响应json',
            'tcp.request_time' => '响应时间',
        ],
        self::PROTOCOL_UDP => [
            'udp.body' => '响应内容',
            'udp.body_length' => 'body长度',
            'udp.body_json' => '响应json',
            'udp.request_time' => '响应时间',
        ],
        self::PROTOCOL_DNS => [
            'dns.host' => '原host',
            'dns.type' => 'DNS类型',
            'dns.target' => '解析别名',
            'dns.ip' => 'ipv4地址',
        ],
    ];

    /**
     * HTTP协议-请求方式.
     */
    public static $confHttpMethods = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'PATCH' => 'PATCH',
        'DELETE' => 'DELETE',
        'HEAD' => 'HEAD',
    ];

    public static $confHttpBodyTypes = [
        self::CONF_HTTP_BODY_TYPE_JSON => 'JSON',
        self::CONF_HTTP_BODY_TYPE_TEXT => 'text/plain',
        self::CONF_HTTP_BODY_TYPE_X_WWW_FORM => 'x-www-form-urlencoded',
        self::CONF_HTTP_BODY_TYPE_FORM_DATA => 'form-data',
        self::CONF_HTTP_BODY_TYPE_NONE => 'none',
    ];

    public static $confDnsTypes = [
        self::CONF_DNS_A => 'A',
        self::CONF_DNS_MX => 'MX',
        self::CONF_DNS_CNAME => 'CNAME',
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
        'contain' => '包含',
        'not-contain' => '不包含',
        'ip-mask' => 'IP掩码',
        'domain-suffix' => '域名后缀',
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
     * 支持的监控频率.
     */
    public static $monitorFrequencies = [
        1 * 60, 2 * 60, 3 * 60, 5 * 60, 10 * 60, 15 * 60, 30 * 60, 60 * 60,
    ];

    protected $table = 'monitor_protocol_detect';

    protected $fillable = [
        'task_id', 'name', 'pinyin', 'remark', 'token', 'protocol', 'monitor_frequency', 'config',
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
     * @param null|mixed $status
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     */
    public function list(
        $departmentId = null,
        $taskId = null,
        $status = null,
        $page = 1,
        $pageSize = 20,
        $search = null,
        $order = []
    ) {
        $builder = $this->with('creator')->with('task')->with('task.department')
            ->select(
                'id',
                'protocol',
                'name',
                'remark',
                'status',
                'created_at',
                'updated_at',
                'created_by',
                'task_id'
            );

        if ($taskId) {
            $builder->where('task_id', $taskId);
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
            ->load('task.department');

        $task->setAttribute('alarm_condition', [
            'conditions' => $this->fmtConditionsForShow($task['alarm_condition']['conditions']),
        ]);

        return $task;
    }

    /**
     * 删除.
     * @param mixed $taskId
     */
    public function deleteTask($taskId, User $user)
    {
        $task = $this->getByIdAndThrow($taskId);
        $task->delete();
    }

    /**
     * 简单列表.
     * @param null|mixed $search
     * @param null|mixed $pageSize
     */
    public function simpleList($search = null, $pageSize = null)
    {
        $builder = $this->select('id', 'protocol', 'name', 'status');

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
            throw new AppException("protocol detect monitor task [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
            ]);
        }

        $alarmTask = $this->alarmTask->getById($param['task_id'], true);

        // 验证数组类型是否正确
        $param['config'] = $this->validAndFmtConfig($param);
        $param['alarm_condition'] = $this->validAndFmtAlarmCondition($param['alarm_condition'], $param['protocol']);

        $data = [
            'task_id' => $param['task_id'],
            'name' => $param['name'],
            'pinyin' => $this->pinyin->convert($param['name']),
            'remark' => $param['remark'],
            'token' => sha1(uniqid()),
            'protocol' => $param['protocol'],
            'monitor_frequency' => $param['monitor_frequency'],
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

        return $task;
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
            throw new AppException("protocol detect monitor task [{$param['name']}] exists, please use other name", [
                'name' => $param['name'],
                'exclude_id' => $taskId,
            ]);
        }

        $task = $this->getByIdAndThrow($taskId);

        $alarmTask = $this->alarmTask->getById($param['task_id'], true);

        // 验证数组类型是否正确
        $param['config'] = $this->validAndFmtConfig($param);
        $param['alarm_condition'] = $this->validAndFmtAlarmCondition($param['alarm_condition'], $param['protocol']);

        $task['task_id'] = $param['task_id'];
        $task['name'] = $param['name'];
        $task['pinyin'] = $this->pinyin->convert($param['name']);
        $task['remark'] = $param['remark'];
        $task['protocol'] = $param['protocol'];
        $task['monitor_frequency'] = $param['monitor_frequency'];
        $task['config'] = $param['config'];
        $task['alarm_condition'] = $param['alarm_condition'];
        $task['updated_at'] = time();

        // 如果任务处于启动状态，则修改任务状态为EDITED
        if ($task['status'] == self::STATUS_STARTED) {
            $task['status'] = self::STATUS_EDITED;
        }

        $task->save();

        $task->load('creator');

        return $task;
    }

    /**
     * 验证连接是否可用.
     *
     * @param array $param
     * @return array
     */
    public function validConnect($param)
    {
        $detect = ProtocolDetectFactory::create($param['protocol'], $param['config']);
        $detect->validConfig();
        return $detect->validConnect();
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
     * @param mixed $protocol
     */
    protected function validAndFmtAlarmCondition($param, $protocol)
    {
        $protocolName = self::$protocols[$protocol];

        $conditions = [];

        // conditions不可以为空
        if (empty($param['conditions']) || ! is_array($param['conditions'])) {
            throw new AppException('alarm conditions cannot be empty');
        }

        foreach ($param['conditions'] as $items) {
            // 告警级别
            if (! isset($items['level']) || ! is_integer($items['level'])) {
                throw new AppException('alarm condtions.*.level cannot be empty');
            }
            if (! AlarmTask::hasAlarmLevel($items['level'])) {
                throw new AppException('alarm conditions.*.level invalid');
            }

            // 告警条件规则
            if (empty($items['rule']) || ! is_array($items['rule'])) {
                throw new AppException('alarm conditions.*.rule connot be empty');
            }
            $rules = [
                'level' => (int) $items['level'],
                'rule' => [],
            ];
            foreach ($items['rule'] as $item) {
                if (empty($item['field']) || ! is_string($item['field'])) {
                    throw new AppException('alarm conditions.*.rule.field cannot be empty');
                }
                // 判断字段是否合法
                $this->validProtocolFields($item['field'], $protocol);

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
     * 验证协议字段.
     * @param mixed $field
     * @param mixed $protocol
     */
    protected function validProtocolFields($field, $protocol)
    {
        foreach (self::$protocolAlarmFields[$protocol] as $allowField => $title) {
            // 命中立马退出
            if (strpos($field, $allowField) === 0) {
                return;
            }
        }

        $protocolName = self::$protocols[$protocol];
        throw new AppException("invalid alarm condition field `{$field}` in protocol {$protocolName}", [
            'protocol' => $protocol,
            'protocol_name' => $protocolName,
            'field' => $field,
        ]);
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
            return "{$item['field']}#{$item['operator']}#{$threshold}";
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
        $detect = ProtocolDetectFactory::create($param['protocol'], $param['config']);
        $config = $detect->validConfig();
        $detect->validConfig();

        return $config;
    }
}
