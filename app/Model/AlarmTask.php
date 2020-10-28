<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Exception\UnauthorizedException;
use App\Service\Pinyin;
use App\Support\ConditionArr;
use App\Support\MySQL;
use Dog\Noticer\Channel\DingGroup;
use Dog\Noticer\Channel\YachGroup;
use Dog\Noticer\Component\Guzzle;
use GuzzleHttp\Client;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Arr;
use stdClass;
use Throwable;

class AlarmTask extends Model
{
    /**
     * 收敛方式.
     */
    // 条件收敛
    public const COMPRESS_METHOD_CONDITION = 1;

    // 相似收敛
    public const COMPRESS_METHOD_SIMHASH = 2;

    // 内容收敛
    public const COMPRESS_METHOD_CONTENT = 3;

    // 全量收敛
    public const COMPRESS_METHOD_FULL = 4;

    /**
     * 收敛策略.
     */
    // 周期收敛
    public const COMPRESS_STRATEGY_CYCLE = 1;

    // 延迟收敛
    public const COMPRESS_STRATEGY_DELAY = 2;

    // 周期次数收敛
    public const COMPRESS_STRATEGY_CYCLE_TIMES = 3;

    // 次数周期收敛
    public const COMPRESS_STRATEGY_TIMES_CYCLE = 4;

    // 次数收敛
    public const COMPRESS_STRATEGY_TIMES = 5;

    /**
     * 告警级别.
     */
    public const ALARM_LEVEL_EXTEND = 9;

    /**
     * 自动恢复方式.
     */
    // 条件恢复
    public const RECOVERY_MODE_CONDITION = 1;

    // 延迟恢复
    public const RECOVERY_MODE_DELAY = 2;

    /**
     * 任务状态
     */
    // 已停止
    public const STATUS_STOPPED = 0;

    // 运行中
    public const STATUS_RUNNING = 1;

    // 已暂停
    public const STATUS_PAUSE = 2;

    // 通知
    public const LEVEL_NOTICE = 0;

    // 警告
    public const LEVEL_WARNING = 1;

    // 错误
    public const LEVEL_ERROR = 2;

    // 紧急
    public const LEVEL_EMERGENCY = 3;

    /**
     * 分级告警-匹配模式.
     */
    public const RECV_DISPATCH_MODE_LAZY = 1;

    public const RECV_DISPATCH_MODE_UNLAZY = 2;

    public $timestamps = false;

    public static $compressAvailableMethods = [
        self::COMPRESS_METHOD_CONDITION => '条件收敛',
        // self::COMPRESS_METHOD_SIMHASH => '相似收敛',
        self::COMPRESS_METHOD_CONTENT => '内容收敛',
        self::COMPRESS_METHOD_FULL => '全量收敛',
    ];

    /**
     * 条件操作符.
     */
    public static $availableConditionOperators = [
        'eq-self' => '等于自身',
        'eq' => '等于',
        'neq' => '不等于',
        'isset' => '字段存在',
        'not-isset' => '字段不存在',
        'lt' => '小于',
        'gt' => '大于',
        'lte' => '不大于',
        'gte' => '不小于',
        'in' => '在范围内',
        'not-in' => '不在范围内',
        'contain' => '包含',
        'not-contain' => '不包含',
    ];

    /**
     * 不带阈值的条件操作符.
     */
    public static $noThresholdOperators = [
        'eq-self', 'isset', 'not-isset',
    ];

    /**
     * 需要拆分为数组的阈值操作符.
     */
    public static $explodeThresholdOperators = [
        'in', 'not-in',
    ];

    /**
     * 拆分阈值的符号.
     */
    public static $explodeThresholdSymbol = '|';

    public static $alarmLevels = [
        0 => '通知',
        1 => '警告',
        2 => '错误',
        3 => '紧急',
        self::ALARM_LEVEL_EXTEND => '继承',
    ];

    public static $recvDispatchModes = [
        self::RECV_DISPATCH_MODE_LAZY => '懒惰模式',
        self::RECV_DISPATCH_MODE_UNLAZY => '非懒惰模式',
    ];

    /**
     * 告警级别.
     *
     * @var array
     */
    public static $levels = [
        self::LEVEL_NOTICE => '通知',
        self::LEVEL_WARNING => '警告',
        self::LEVEL_ERROR => '错误',
        self::LEVEL_EMERGENCY => '紧急',
    ];

    protected $table = 'alarm_task';

    protected $fillable = ['name', 'pinyin', 'token', 'secret', 'department_id', 'flag_save_db', 'enable_workflow', 'enable_filter', 'enable_compress', 'enable_upgrade', 'enable_recovery', 'status', 'created_by', 'created_at', 'updated_at', 'props'];

    protected $casts = [
        'props' => 'array',
    ];

    /**
     * @Inject
     * @var AlarmTaskTag
     */
    protected $alarmTaskTag;

    /**
     * @Inject
     * @var AlarmGroup
     */
    protected $alarmGroup;

    /**
     * @Inject
     * @var Department
     */
    protected $department;

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * @Inject
     * @var AlarmTaskPermission
     */
    protected $alarmTaskPermission;

    /**
     * @Inject
     * @var AlarmTaskAlarmGroup
     */
    protected $alarmTaskAlarmGroup;

    /**
     * @Inject
     * @var AlarmTemplate
     */
    protected $alarmTemplate;

    public static function hasCompressMethod($method)
    {
        return isset(static::$compressAvailableMethods[$method]);
    }

    public static function hasCompressStrategy($strategy)
    {
        return in_array($strategy, [
            static::COMPRESS_STRATEGY_CYCLE,
            static::COMPRESS_STRATEGY_DELAY,
            static::COMPRESS_STRATEGY_CYCLE_TIMES,
            static::COMPRESS_STRATEGY_TIMES_CYCLE,
            static::COMPRESS_STRATEGY_TIMES,
        ]);
    }

    public static function hasCondOperator($operator)
    {
        return isset(static::$availableConditionOperators[$operator]);
    }

    /**
     * 告警级别是否合法.
     *
     * @param int $level
     * @param bool $includeExtend 是否包含继承类型告警级别
     * @return bool
     */
    public static function hasAlarmLevel($level, $includeExtend = false)
    {
        if ($includeExtend) {
            return isset(static::$alarmLevels[$level]);
        }
        return isset(static::$alarmLevels[$level]) && ($level != static::ALARM_LEVEL_EXTEND);
    }

    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * @param int $taskId
     * @param bool $throwable 任务不存在时是否抛出异常
     * @return self
     */
    public function getById($taskId, $throwable = false)
    {
        $task = $this->where('id', $taskId)->first();
        if ($throwable && empty($task)) {
            throw new AppException("task [{$taskId}] not found", [
                'task_id' => $taskId,
            ]);
        }

        return $task;
    }

    public function department()
    {
        return $this->hasOne(Department::class, 'id', 'department_id')->select('id', 'name');
    }

    public function taskTags()
    {
        return $this->belongsToMany(
            AlarmTag::class,
            'alarm_task_tag',
            'task_id',
            'tag_id',
            'id'
        )->select('alarm_tag.id', 'name', 'remark', 'created_by')->with('creator');
    }

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')->select('uid', 'username', 'email', 'department');
    }

    /**
     * 告警任务列表.
     * @param mixed $user
     */
    public function filterTasksByUser($user)
    {
        if ($user->isAdmin()) {
            return false;
        }

        return $this->alarmTaskPermission
            ->select(Db::raw('DISTINCT(`task_id`)'))
            ->where('uid', $user['uid'])
            ->get();
    }

    /**
     * 告警任务列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     * @param null|mixed $departmentId
     * @param null|mixed $tagId
     * @param mixed $user
     */
    public function list($page = 1, $pageSize = 20, $search = null, $order = [], $departmentId = null, $tagId = null, $user = true)
    {
        $builder = $this->select('id', 'name', 'department_id', 'created_at', 'status', 'created_by')
            ->with('department', 'creator', 'taskTags');

        if ($user !== true) {
            if (($taskIds = $this->filterTasksByUser($user)) !== false) {
                $builder->whereIn('id', $taskIds);
            }
        }

        if ($tagId) {
            $tasksIdBytagId = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            $builder->whereIn('id', $tasksIdBytagId);
        }
        if ($departmentId) {
            $builder->where('department_id', $departmentId);
        }
        if ($search) {
            $builder->where(function ($query) use ($search) {
                if (is_numeric($search)) {
                    $query->where('id', $search);
                }
                $query->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    /**
     * 告警任务简单列表.
     * @param null $pageSize
     * @param null $search
     * @param null $departmentId
     * @param null|User $user
     * @return array
     */
    public function simpleList($pageSize = null, $search = null, $departmentId = null, $user = null)
    {
        $builder = $this->select('id', 'name', 'department_id', 'pinyin');

        // 权限验证
        if ($user && ! $user->isAdmin()) {
            $taskIds = $this->alarmTaskPermission->getTaskIdByUid($user['uid']);
            $builder->whereIn('id', $taskIds);
        }

        if ($pageSize) {
            $builder->limit((int) $pageSize);
        }
        if ($departmentId) {
            $builder->where('department_id', $departmentId);
        }
        if ($search) {
            $builder->where(function ($query) use ($search) {
                if (is_numeric($search)) {
                    $query->where('id', $search);
                }
                $query->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('pinyin', 'like', "%{$search}%");
            });
        }

        return $builder->get();
    }

    /**
     * 是否有读写权限.
     *
     * @param int $uid
     * @param int $taskId
     * @return bool
     */
    public function hasPermissonRW($uid, $taskId)
    {
        $user = User::where('uid', $uid)->select('uid', 'username', 'email', 'department', 'role')->first();
        if (empty($user)) {
            throw new AppException('当前用户不存在', [
                'uid' => $uid,
            ]);
        }

        // 超管直接允许通过
        if ($user->role == User::ROLE_ADMIN) {
            return true;
        }

        $hasPermission = AlarmTaskPermission::where('uid', $uid)
            ->where('task_id', $taskId)
            ->where('type', AlarmTaskPermission::TYPE_RW)
            ->count();

        return (bool) $hasPermission;
    }

    /**
     * 删除.
     * @param mixed $taskId
     * @param mixed $user
     */
    public function deleteTask($taskId, $user)
    {
        $task = $this->getById($taskId, true);

        // 临时屏蔽OpenAPI模块校验
        if ($user !== true) {
            if (! $this->hasPermissonRW($user['uid'], $taskId)) {
                throw new ForbiddenException('您没有权限删除');
            }
        }

        Db::beginTransaction();
        try {
            $task->delete();

            // 删除关联数据
            AlarmTaskConfig::where('task_id', $taskId)->delete();
            AlarmTaskPermission::where('task_id', $taskId)->delete();
            AlarmTaskAlarmGroup::where('task_id', $taskId)->delete();
            AlarmTaskTag::where('task_id', $taskId)->delete();

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 停止任务
     * @param mixed $taskId
     * @param mixed $user
     */
    public function stopTask($taskId, $user = true)
    {
        $task = $this->getById($taskId, true);

        // 临时屏蔽OpenAPI模块校验
        if ($user !== true) {
            if (! $this->hasPermissonRW($user['uid'], $taskId)) {
                throw new ForbiddenException('您没有权限停止');
            }
        }

        if ($task->status == static::STATUS_STOPPED) {
            throw new AppException('task was stopped, you cannot stop it repeatedly');
        }

        $task->status = static::STATUS_STOPPED;
        $task->save();
    }

    /**
     * 启动任务
     * @param mixed $taskId
     * @param mixed $user
     */
    public function startTask($taskId, $user = true)
    {
        $task = $this->getById($taskId, true);

        // 临时屏蔽OpenAPI模块校验
        if ($user !== true) {
            if (! $this->hasPermissonRW($user['uid'], $taskId)) {
                throw new ForbiddenException('您没有权限启动');
            }
        }

        if ($task->status == static::STATUS_RUNNING) {
            throw new AppException('task was running, you cannot run it repeatedly');
        }

        $task->status = static::STATUS_RUNNING;
        $task->save();
    }

    /**
     * 暂停任务
     * @param mixed $taskId
     * @param mixed $interval
     * @param mixed $user
     */
    public function pauseTask($taskId, $interval, $user = true)
    {
        $task = $this->getById($taskId, true);

        // 临时屏蔽OpenAPI模块校验
        if ($user !== true) {
            if (! $this->hasPermissonRW($user['uid'], $taskId)) {
                throw new ForbiddenException('您没有权限暂停');
            }
        }

        Db::beginTransaction();
        try {
            $task->status = static::STATUS_PAUSE;
            $task->save();

            // 写入暂停时间
            make(DelayQueueAlarmTaskPause::class)->setQueue($taskId, $interval);

            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 重置token.
     * @param mixed $taskId
     * @param mixed $user
     */
    public function resetToken($taskId, $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以重置token，请联系系统下方管理员！');
        }

        $task = $this->getById($taskId, true);

        $task->token = sha1(uniqid());
        $task->save();

        return $task;
    }

    /**
     * 重置secret.
     * @param mixed $taskId
     * @param mixed $user
     */
    public function resetSecret($taskId, $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以重置secret，请联系系统下方管理员！');
        }

        $task = $this->getById($taskId, true);

        $task->secret = sha1(uniqid());
        $task->save();

        return $task;
    }

    /**
     * 请求WebHook地址并返回状态码
     * @param mixed $param
     */
    public function validWebHookAddress($param)
    {
        $guzzle = Guzzle::create();
        $resp = $guzzle->post($param['webhook_url'], ['connect_timeout' => 0.2]);

        if (($statusCode = $resp->getStatusCode()) !== 200) {
            throw new AppException('WebHook address error, got ' . $statusCode);
        }
    }

    /**
     * 只能校验Yach或Ding的机器人参数并发送一条消息到群组.
     * @param mixed $param
     */
    public function validateRobotParam($param)
    {
        $robots = [
            ['webhook' => $param['webtoken'], 'secret' => $param['secret']],
        ];
        $content = '哮天犬: WebToken 与 Secret 参数验证成功，请忽略此条消息。';
        $group = $param['channels'] == AlarmGroup::CHANNEL_DINGGROUP ? make(DingGroup::class) : make(YachGroup::class);
        $text = $param['channels'] == AlarmGroup::CHANNEL_DINGGROUP ? new DingGroup\MsgType\Text($content) : new YachGroup\MsgType\Text($content);

        $group->send($text, $robots);
    }

    /**
     * 上报告警信息.
     * @param mixed $taskId
     * @param mixed $param
     */
    public function reportAlarm($taskId, $param)
    {
        $task = $this->getById($taskId, true);

        /**
         * @var Client
         */
        $guzzle = Guzzle::create(config('guzzle'));

        $time = time();
        $url = config('app.base_uri_api_alarm') . '/alarm/report';
        $reqJson = [
            'taskid' => $task->id,
            'timestamp' => $time,
            'sign' => md5($task->id . '&' . $time . $task->token),
            'level' => $param['level'],
            'notice_time' => $param['notice_time'],
            'ctn' => $param['ctn'],
        ];

        $resp = $guzzle->post($url, [
            'json' => $reqJson,
        ]);

        // 判断响应结果是否正常
        if (($statusCode = $resp->getStatusCode()) !== 200) {
            throw new AppException('alarm report failed: status code not 200, got ' . $statusCode, [
                'status_code' => $statusCode,
            ]);
        }
        $body = (string) $resp->getBody()->getContents();
        $respJson = json_decode($body, true);
        if (! $respJson || ! isset($respJson['code'])) {
            throw new AppException('alarm report failed: response body is not json, body is ' . $body, [
                'body' => $body,
            ]);
        }
        if ($respJson['code']) {
            throw new AppException('alarm report failed: ' . $respJson['msg'], [
                'code' => $respJson['code'],
                'msg' => $respJson['msg'],
            ]);
        }

        return $respJson['data'];
    }

    /**
     * 告警详情.
     * @param mixed $taskId
     * @param mixed $user
     */
    public function showTask($taskId, $user = true)
    {
        $task = $this->getById($taskId, true);
        if ($user !== true) {
            $this->validatePermission($taskId, $user);
        }
        $taskConfig = AlarmTaskConfig::where('task_id', $taskId)->first();
        $permission = AlarmTaskPermission::where('task_id', $taskId)->get();

        $receiver = json_decode($taskConfig->receiver, true);
        $workflow = json_decode($taskConfig->workflow, true);
        $filter = json_decode($taskConfig->filter, true);
        $upgrade = json_decode($taskConfig->upgrade, true);
        $compress = json_decode($taskConfig->compress, true);
        $recovery = json_decode($taskConfig->recovery, true);
        $template = json_decode($taskConfig->alarm_template, true);

        [$users, $groups] = $this->getUidAndGroupIdForShow($task, $permission, $receiver, $upgrade, $workflow);

        $task->load('department');
        $task->setHidden([
            'enable_workflow', 'enable_filter', 'enable_compress', 'enable_upgrade', 'enable_recovery',
        ]);
        $respJson = $task->toArray();
        $respJson['creator'] = $users[$task->created_by] ?? null;
        $respJson['permission'] = $this->fmtPermissionForShow($users, $permission);
        $respJson['receiver'] = $this->fmtReceiverModuleForShow($users, $groups, $receiver);
        $respJson['workflow'] = $this->fmtWorkflowForShow($task, $users, $groups, $workflow);
        $respJson['filter'] = $this->fmtFilterForShow($task, $filter);
        $respJson['upgrade'] = $this->fmtUpgradeForShow($task, $users, $groups, $upgrade);
        $respJson['compress'] = $this->fmtCompressForShow($task, $compress);
        $respJson['recovery'] = $this->fmtRecoverForShow($task, $recovery);
        $respJson['template'] = $this->fmtTemplateForShow($template, $taskConfig->alarm_template_id);
        $respJson['task_tags'] = $this->alarmTaskTag->showFollowTag($taskId);
        return $respJson;
    }

    /**
     * 生成一个可用签名.
     * @param mixed $task
     */
    public function genSign($task)
    {
        $taskid = $task['id'];
        $timestamp = time();
        $sign = md5(sprintf('%s&%s%s', $taskid, $timestamp, $task['token']));

        return [
            'taskid' => $taskid,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'base_uri' => config('app.base_uri_api'),
        ];
    }

    /**
     * 告警详情原始信息.
     * @param mixed $taskId
     */
    public function taskRaw($taskId)
    {
        $task = $this->getById($taskId, true);
        $taskConfig = AlarmTaskConfig::where('task_id', $taskId)->first();
        $permission = AlarmTaskPermission::where('task_id', $taskId)->get();

        $receiver = json_decode($taskConfig->receiver, true);
        $workflow = json_decode($taskConfig->workflow, true);
        $filter = json_decode($taskConfig->filter, true);
        $upgrade = json_decode($taskConfig->upgrade, true);
        $compress = json_decode($taskConfig->compress, true);
        $recovery = json_decode($taskConfig->recovery, true);
        $template = json_decode($taskConfig->alarm_template, true);

        $respJson = $task->toArray();
        $respJson['permission'] = $this->fmtPermissionForRaw($permission);
        $respJson['receiver'] = $this->fmtReceiverModuleForRaw($receiver);
        $respJson['workflow'] = $this->fmtWorkflowForRaw($task, $workflow);
        $respJson['filter'] = $this->fmtFilterForRaw($task, $filter);
        $respJson['upgrade'] = $this->fmtUpgradeForRaw($task, $upgrade);
        $respJson['compress'] = $this->fmtCompressForRaw($task, $compress);
        $respJson['recovery'] = $this->fmtRecoverForRaw($task, $recovery);
        $respJson['template'] = $this->fmtTemplateForRaw($template, $taskConfig->alarm_template_id);

        return $respJson;
    }

    /**
     * 快速校验openapi签名.
     *
     * @param array $param
     * @return self
     */
    public static function fastCheckOpenApi($param)
    {
        if (empty($param['id']) || empty($param['timestamp']) || empty($param['sign'])) {
            throw new UnauthorizedException('missing task sign fields');
        }

        $task = self::where('id', $param['id'])->first();
        if (empty($task)) {
            throw new AppException("task [{$param['id']}] not found", [
                'task_id' => $param['id'],
            ]);
        }

        // TODO DEBUG
        return $task;
        // 签名校验
        if ($param['sign'] !== md5($param['id'] . '&' . $param['timestamp'] . $task->secret)) {
            throw new AppException('task signature invalid', [
                'sign' => $param['sign'],
            ]);
        }

        return $task;
    }

    public function storeTask($params, $user)
    {
        $time = time();

        // 重名
        if ($this->hasByName($params['name'])) {
            throw new AppException("task [{$params['name']}] already exists");
        }

        // 参数校验
        $department = $this->department->getByIdAndThrow($params['department_id']);
        $permission = $this->validAndFormatPermission($params['permission'] ?? [], $user, true);
        $receiver = $this->validAndFormatReceiverModule($params['receiver'] ?? []);
        $workflow = $this->validAndFormatWorkflow($params['workflow'] ?? []);
        $filter = $this->validAndFormatFilter($params['filter'] ?? []);
        $upgrade = $this->validAndFormatUpgrade($params['upgrade'] ?? []);
        $compress = $this->validAndFormatCompress($params['compress'] ?? []);
        $recovery = $this->validAndFormatRecovery($params['recovery'] ?? []);
        $template = $this->validAndFormatTemplate($params['template'] ?? []);
        $nullPhoneUsers = $this->validPhoneByUid($receiver, $workflow, $upgrade);

        Db::beginTransaction();
        try {
            $taskData = [
                'name' => $params['name'],
                'pinyin' => $this->pinyin->convert($params['name']),
                'token' => sha1(uniqid()),
                'secret' => sha1(uniqid()),
                'department_id' => $params['department_id'],
                'flag_save_db' => $params['flag_save_db'],
                'enable_workflow' => $workflow ? 1 : 0,
                'enable_filter' => $filter ? 1 : 0,
                'enable_compress' => $compress ? 1 : 0,
                'enable_upgrade' => $upgrade ? 1 : 0,
                'enable_recovery' => $recovery ? 1 : 0,
                'status' => AlarmTask::STATUS_RUNNING,
                'created_by' => $user['uid'],
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $task = AlarmTask::create($taskData);

            // 任务配置
            $configData = [
                'task_id' => $task->id,
                'workflow' => json_encode($workflow['workflow'] ?: new stdClass()),
                'compress' => json_encode($compress ?: new stdClass()),
                'filter' => json_encode($filter ?: new stdClass()),
                'recovery' => json_encode($recovery ?: new stdClass()),
                'upgrade' => json_encode($upgrade['upgrade'] ?: new stdClass()),
                'receiver' => json_encode($receiver ?: new stdClass()),
                'alarm_template_id' => $template['template_id'],
                'alarm_template' => json_encode($template['template'] ?: new stdClass()),
            ];
            $taskConfig = AlarmTaskConfig::create($configData);

            $this->alarmTaskPermission->savePermission($task->id, $permission, false);

            // TODO
            // digngroupfoucs

            // alarmGroup关联
            $this->alarmTaskAlarmGroup->saveGroups($task->id, [
                'receiver' => $receiver,
                'workflow' => $workflow,
                'upgrade' => $upgrade,
            ], false);

            // 关联标签
            $this->alarmTaskTag->follow($params['task_tags'] ?? [], $task->id);

            Db::commit();

            return [$task, $nullPhoneUsers];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function updateTask($taskId, $params, $user)
    {
        $task = $this->getById($taskId, true);

        // 临时屏蔽OpenAPI模块校验
        if ($user !== true) {
            if (! $this->hasPermissonRW($user['uid'], $taskId)) {
                throw new ForbiddenException('您没有权限编辑告警任务');
            }
        }

        // 重名
        if ($this->hasByName($params['name'], $taskId)) {
            throw new AppException("task [{$params['name']}] already exists");
        }

        // 参数校验
        $department = $this->department->getByIdAndThrow($params['department_id']);
        $permission = $this->validAndFormatPermission($params['permission'] ?? []);
        $receiver = $this->validAndFormatReceiverModule($params['receiver'] ?? []);
        $workflow = $this->validAndFormatWorkflow($params['workflow'] ?? []);
        $filter = $this->validAndFormatFilter($params['filter'] ?? []);
        $upgrade = $this->validAndFormatUpgrade($params['upgrade'] ?? []);
        $compress = $this->validAndFormatCompress($params['compress'] ?? []);
        $recovery = $this->validAndFormatRecovery($params['recovery'] ?? []);
        $template = $this->validAndFormatTemplate($params['template'] ?? []);
        $nullPhoneUsers = $this->validPhoneByUid($receiver, $workflow, $upgrade);

        Db::beginTransaction();
        try {
            $task->name = $params['name'];
            $task->pinyin = $this->pinyin->convert($params['name']);
            $task->department_id = $params['department_id'];
            $task->flag_save_db = $params['flag_save_db'];
            $task->enable_workflow = $workflow ? 1 : 0;
            $task->enable_filter = $filter ? 1 : 0;
            $task->enable_compress = $compress ? 1 : 0;
            $task->enable_upgrade = $upgrade ? 1 : 0;
            $task->enable_recovery = $recovery ? 1 : 0;
            $task->updated_at = time();
            $task->save();

            // 任务配置
            $configData = [
                'workflow' => json_encode($workflow['workflow'] ?: new stdClass()),
                'compress' => json_encode($compress ?: new stdClass()),
                'filter' => json_encode($filter ?: new stdClass()),
                'recovery' => json_encode($recovery ?: new stdClass()),
                'upgrade' => json_encode($upgrade['upgrade'] ?: new stdClass()),
                'receiver' => json_encode($receiver ?: new stdClass()),
                'alarm_template_id' => $template['template_id'],
                'alarm_template' => json_encode($template['template'] ?: new stdClass()),
            ];
            AlarmTaskConfig::where('task_id', $task->id)->update($configData);

            $this->alarmTaskPermission->savePermission($task->id, $permission, true);

            // 更新标签
            $this->alarmTaskTag->updateFollowTag($params['task_tags'] ?? [], $taskId);

            // TODO
            // digngroupfoucs

            // alarmGroup关联
            $this->alarmTaskAlarmGroup->saveGroups($task->id, [
                'receiver' => $receiver,
                'workflow' => $workflow,
                'upgrade' => $upgrade,
            ], true);

            Db::commit();

            return [$task, $nullPhoneUsers];
        } catch (Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 更新过滤的字段.
     * @param mixed $taskId
     * @param mixed $params
     * @param mixed $user
     * @param mixed $updateFields
     */
    public function updateFilterFields($taskId, $params, $user, $updateFields = [])
    {
        // 取出原始数据
        $task = $this->taskRaw($taskId);
        $task = array_only_keys($task, [
            'name', 'department_id', 'flag_save_db', 'permission', 'receiver', 'workflow', 'filter', 'upgrade',
            'compress', 'recovery', 'template',
        ]);
        if (isset($task['name'])) {
            $task['pinyin'] = $this->pinyin->convert($task['name']);
        }

        // 覆盖要更新的值
        foreach ($updateFields as $field) {
            Arr::set($task, $field, Arr::get($params, $field, null));
        }

        return $this->updateTask($taskId, $task, $user);
    }

    public function validAndFormatTemplate($params)
    {
        $template = [];
        if (empty($params['template_id']) || ! is_numeric($params['template_id'])) {
            // 未选择预定义模板
            $template['template_id'] = 0;
        } else {
            // 选择自定义模板
            if (! AlarmTemplate::where('id', $params['template_id'])->count()) {
                throw new AppException("template [{$params['template_id']}] not found");
            }
            $template['template_id'] = (int) $params['template_id'];
        }

        // 自定义模板
        $template['template'] = [];
        if (! $template['template_id'] && ! empty($params['udf'])) {
            $template['template'] = $this->alarmTemplate->validAndFormat($params['udf'], false);
        }

        return $template;
    }

    /**
     * 批量通过任务ID查询任务
     */
    public function getStatusPauses(array $taskIds): array
    {
        $builder = $this->select('id')
            ->where('status', self::STATUS_PAUSE)
            ->whereIn('id', $taskIds)
            ->get();

        return ! $builder ? [] : $builder->toArray();
    }

    /**
     * 修改暂停状态为运行状态
     */
    public function updatePauseToRunning(int $time, array $taskIds): bool
    {
        return $this->whereIn('id', $taskIds)
            ->update([
                'status' => self::STATUS_RUNNING,
                'updated_at' => $time,
            ]);
    }

    /**
     * 获取部门数据通过任务ID.
     */
    public function getDepartmentByTaskIds(array $taskIds): array
    {
        $ret = $this->select('id', 'name', 'department_id')
            ->with('department')
            ->whereIn('id', $taskIds)
            ->get();

        return ! $ret ? [] : $ret->toArray();
    }

    /**
     * 临时通知渠道：校验电话或短信通知渠道通知人有无手机号.
     * @param mixed $receiver
     * @param mixed $workflow
     * @param mixed $upgrade
     */
    public function validPhoneByUid($receiver, $workflow, $upgrade)
    {
        $phoneUid = [];
        $smsUid = [];

        if (isset($receiver['channels']['phone'])) {
            $phoneUid = array_merge($phoneUid, $receiver['channels']['phone']);
        }
        if (isset($receiver['channels']['sms'])) {
            $smsUid = array_merge($smsUid, $receiver['channels']['sms']);
        }

        if (isset($workflow['workflow']['reminds'])) {
            foreach ($workflow['workflow']['reminds'] as $reminds) {
                if (isset($reminds['receiver']['channels']['phone'])) {
                    $phoneUid = array_merge($phoneUid, $reminds['receiver']['channels']['phone']);
                }
                if (isset($reminds['receiver']['channels']['sms'])) {
                    $smsUid = array_merge($smsUid, $reminds['receiver']['channels']['sms']);
                }
            }
        }

        if (isset($upgrade['upgrade']['strategies'])) {
            foreach ($upgrade['upgrade']['strategies'] as $strategies) {
                if (isset($strategies['receiver']['channels']['phone'])) {
                    $phoneUid = array_merge($phoneUid, $strategies['receiver']['channels']['phone']);
                }
                if (isset($strategies['receiver']['channels']['sms'])) {
                    $smsUid = array_merge($smsUid, $strategies['receiver']['channels']['sms']);
                }
            }
        }

        $channelsPhoneSmsUid = array_unique(array_merge($smsUid, $phoneUid));

        return empty($channelsPhoneSmsUid)
            ? []
            : User::whereIn('uid', $channelsPhoneSmsUid)
                ->where('phone', '')
                ->select('uid', 'user', 'username', 'email', 'department')
                ->get();
    }

    /**
     * 显示任务阈值
     * @param mixed $taskId
     */
    public function getRateLimit($taskId)
    {
        $defaultTaskRateLimit = config('alarmtaskconfig.default_rate_limit', 200);

        $task = $this->getById($taskId, true);

        $props = $task['props'];

        return $props['rate_limit'] ?? $defaultTaskRateLimit;
    }

    /**
     * 超管修改任务阈值
     * @param mixed $taskId
     * @param mixed $rateLimit
     * @param mixed $user
     */
    public function updateRateLimit($taskId, $rateLimit, $user)
    {
        // 权限判断，仅允许超管
        if (! $user->isAdmin()) {
            throw new ForbiddenException('仅超管可以修改任务阈值');
        }

        $task = $this->getById($taskId, true);

        if ($rateLimit < 1) {
            throw new AppException('输入的阈值必须大于0');
        }
        $props = $task['props'];
        // $threshold 为啥不是 int
        $props['rate_limit'] = (int) $rateLimit;
        $task->props = $props;
        $task->save();

        return $task['props'];
    }

    /**
     * 根据标签和任务名称获取任务列表.
     * @param $tagId
     * @param $search
     * @param $pageSize
     * @return array
     */
    public function simpleByTag($tagId, $search, $pageSize)
    {
        $query = Db::table('alarm_task_tag')
            ->leftJoin('alarm_task', function ($join) use ($search) {
                $join->on('alarm_task_tag.task_id', '=', 'alarm_task.id');
                if ($search) {
                    $join->where('alarm_task.name', 'like', "%{$search}%");
                }
            })->where('alarm_task_tag.tag_id', $tagId)
            ->whereNotNull('alarm_task.name');
        if ($pageSize) {
            $query->limit($pageSize);
        }
        $tasks = $query->get(['alarm_task.id', 'alarm_task.name']);
        return $tasks->all();
    }

    /**
     * 获取taskid或者统计
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @param bool $count
     * @return array|int
     */
    public function getTaskIdsOrCount($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0, $count = false)
    {
        $query = $this->newQuery();
        if ($time) {
            $query->where('created_at', '<=', $time);
        }

        if ($taskId) {
            $query->where('id', $taskId);
        } elseif ($departmentId) {
            $query->where('department_id', $departmentId);
        } elseif ($tagId) {
            $tasksIds = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            $query->whereIn('id', $tasksIds);
        }

        if ($count) {
            return $query->count();
        }
        return $query->pluck('id')->toArray();
    }

    public function getTaskNames($taskIds)
    {
        $query = $this->newQuery()
            ->whereIn('id', $taskIds)
            ->select(['id', 'name']);
        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * 校验权限.
     * @param mixed $taskId
     * @param mixed $user
     */
    public function validatePermission($taskId, $user)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $hasPermission = $this->alarmTaskPermission
            ->where('task_id', $taskId)
            ->where('uid', $user['uid'])
            ->exists();

        if (! $hasPermission) {
            throw new ForbiddenException('您没有权限操作');
        }

        return true;
    }

    /**
     * 获取uid和groupid.
     * @param mixed $task
     * @param mixed $permission
     * @param mixed $receiver
     * @param mixed $upgrade
     * @param mixed $workflow
     */
    protected function getUidAndGroupIdForShow($task, $permission, $receiver, $upgrade, $workflow)
    {
        // 保存用户ID统一查询
        $uids = $permission->pluck('uid')->toArray();
        $uids[] = $task->created_by;
        $groupIds = [];

        [$rUids, $rGroupIds] = $this->getUidAndGroupIdFromReceiver($receiver);
        $uids = array_merge($uids, $rUids);
        $groupIds = array_merge($groupIds, $rGroupIds);
        foreach ($receiver['dispatch'] ?? [] as $item) {
            [$rUids, $rGroupIds] = $this->getUidAndGroupIdFromReceiver($item['receiver']);
            $uids = array_merge($uids, $rUids);
            $groupIds = array_merge($groupIds, $rGroupIds);
        }

        if ($task->enable_upgrade && ! empty($upgrade) && ! empty($upgrade['strategies'])) {
            foreach ($upgrade['strategies'] as $strategy) {
                if (! $strategy['reuse_receiver'] && ! empty($strategy['receiver'])) {
                    [$cUids, $cGroupIds] = $this->getUidAndGroupIdFromReceiver($strategy['receiver']);
                    $uids = array_merge($uids, $cUids);
                    $groupIds = array_merge($groupIds, $cGroupIds);
                }
            }
        }

        if ($task->enable_workflow && ! empty($workflow) && ! empty($workflow['reminds'])) {
            foreach ($workflow['reminds'] as $remind) {
                if (! $remind['reuse_receiver'] && ! empty($remind['receiver'])) {
                    [$cUids, $cGroupIds] = $this->getUidAndGroupIdFromReceiver($remind['receiver']);
                    $uids = array_merge($uids, $cUids);
                    $groupIds = array_merge($groupIds, $cGroupIds);
                }
            }
        }

        // 查询出所有用户
        $users = User::whereIn('uid', $uids)->select('uid', 'username', 'email', 'department')->get()->keyBy('uid');
        $groups = AlarmGroup::whereIn('id', $groupIds)->select('id', 'name')->get()->keyBy('id');

        return [$users, $groups];
    }

    /**
     * 从receiver中获取uid和groupid.
     *
     * @param array $receiver
     * @return array
     */
    protected function getUidAndGroupIdFromReceiver($receiver)
    {
        $uids = [];
        $groupIds = [];
        if (empty($receiver)) {
            return [$uids, $groupIds];
        }

        if (! empty($receiver['alarmgroup'])) {
            $groupIds = $receiver['alarmgroup'];
        }
        if (! empty($receiver['channels'])) {
            foreach (AlarmGroup::$availableChannelsUser as $channel) {
                if (empty($receiver['channels'][$channel])) {
                    continue;
                }
                $uids = array_merge($uids, $receiver['channels'][$channel]);
            }
        }

        return [$uids, $groupIds];
    }

    /**
     * 格式化permission.
     * @param mixed $users
     * @param mixed $permission
     */
    protected function fmtPermissionForShow($users, $permission)
    {
        $data = ['rw' => [], 'ro' => []];
        $scopes = [
            AlarmTaskPermission::TYPE_RW => 'rw',
            AlarmTaskPermission::TYPE_RO => 'ro',
        ];
        foreach ($permission->groupBy('type') as $type => $groupUsers) {
            foreach ($groupUsers as $user) {
                if (isset($users[$user['uid']])) {
                    $data[$scopes[$type]][] = $users[$user['uid']];
                }
            }
        }

        return $data;
    }

    /**
     * 格式化Receiver.
     * @param mixed $users
     * @param mixed $groups
     * @param mixed $receiver
     */
    protected function fmtReceiverForShow($users, $groups, $receiver)
    {
        $data = ['alarmgroup' => [], 'channels' => []];

        if (! empty($receiver['alarmgroup'])) {
            foreach ($receiver['alarmgroup'] as $groupId) {
                if (isset($groups[$groupId])) {
                    $data['alarmgroup'][] = $groups[$groupId];
                }
            }
        }

        $channels = $this->alarmGroup->formatChannels($receiver, $users) ?: new stdClass();
        $data['channels'] = $channels;

        return $data;
    }

    /**
     * 格式化Receiver模块信息.
     * @param mixed $users
     * @param mixed $groups
     * @param mixed $receiver
     */
    protected function fmtReceiverModuleForShow($users, $groups, $receiver)
    {
        $respReceiver = $this->fmtReceiverForShow($users, $groups, $receiver);

        $dispatch = [];
        foreach ($receiver['dispatch'] ?? [] as $item) {
            $this->fmtConditionsForShow($item);
            $item['receiver'] = $this->fmtReceiverForShow($users, $groups, $item['receiver']);
            $dispatch[] = $item;
        }
        $respReceiver['dispatch'] = $dispatch;
        $respReceiver['mode'] = $receiver['mode'] ?? self::RECV_DISPATCH_MODE_LAZY;

        return $respReceiver;
    }

    /**
     * 格式化workflow.
     * @param mixed $task
     * @param mixed $users
     * @param mixed $groups
     * @param mixed $workflow
     */
    protected function fmtWorkflowForShow($task, $users, $groups, $workflow)
    {
        $workflow['enable'] = (bool) $task->enable_workflow;

        if ($workflow['enable']) {
            $reminds = [];
            foreach ($workflow['reminds'] as $remind) {
                if (! $remind['reuse_receiver']) {
                    $remind['receiver'] = $this->fmtReceiverForShow($users, $groups, $remind['receiver']);
                }
                $reminds[] = $remind;
            }
            $workflow['reminds'] = $reminds;
        }

        return $workflow;
    }

    protected function fmtFilterForShow($task, $filter)
    {
        $filter['enable'] = (bool) $task->enable_filter;
        $this->fmtConditionsForShow($filter);
        return $filter;
    }

    protected function fmtUpgradeForShow($task, $users, $groups, $upgrade)
    {
        $upgrade['enable'] = (bool) $task->enable_upgrade;

        if ($upgrade['enable']) {
            $strategies = [];
            foreach ($upgrade['strategies'] as $strategy) {
                if (! $strategy['reuse_receiver']) {
                    $strategy['receiver'] = $this->fmtReceiverForShow($users, $groups, $strategy['receiver']);
                }
                $strategies[] = $strategy;
            }
            $upgrade['strategies'] = $strategies;
        }

        return $upgrade;
    }

    protected function fmtCompressForShow($task, $compress)
    {
        $compress['enable'] = (bool) $task->enable_compress;
        $this->fmtConditionsForShow($compress);
        return $compress;
    }

    protected function fmtRecoverForShow($task, $recovery)
    {
        $recovery['enable'] = (bool) $task->enable_recovery;
        $this->fmtConditionsForShow($receiver);
        return $recovery;
    }

    protected function fmtTemplateForShow($template, $templateId)
    {
        $data = [
            'template_id' => $templateId ? (int) $templateId : null,
        ];
        // 载入预定义模板信息
        if ($data['template_id']) {
            $data['type'] = AlarmTemplate::TYPE_PREDEFINED;
            $data['template'] = $this->alarmTemplate->showTemplate($templateId);
        } else {
            $data['type'] = empty($template) ? AlarmTemplate::TYPE_DEFAULT : AlarmTemplate::TYPE_CUSTOM;
            $data['udf'] = $this->alarmTemplate->fmtAttrTemplate($template, AlarmTemplate::TYPE_CUSTOM);
        }

        return $data;
    }

    protected function fmtConditionsForShow(&$data)
    {
        // 判断conditions为空时
        if (empty($data['conditions']) || ! is_array($data['conditions'])) {
            $data['conditions'] = [];
            return;
        }

        $conditions = [];
        foreach ($data['conditions'] as $items) {
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
        $data['conditions'] = $conditions;
    }

    /**
     * 格式化permission-原始信息.
     * @param mixed $permission
     */
    protected function fmtPermissionForRaw($permission)
    {
        $data = ['rw' => [], 'ro' => []];
        $scopes = [
            AlarmTaskPermission::TYPE_RW => 'rw',
            AlarmTaskPermission::TYPE_RO => 'ro',
        ];
        foreach ($permission->groupBy('type') as $type => $groupUsers) {
            foreach ($groupUsers as $user) {
                $data[$scopes[$type]][] = $user['uid'];
            }
        }

        return $data;
    }

    /**
     * 格式化Receiver-原始信息.
     * @param mixed $receiver
     */
    protected function fmtReceiverForRaw($receiver)
    {
        return $receiver;
    }

    /**
     * 格式化Receiver模块信息-原生信息.
     * @param mixed $receiver
     */
    protected function fmtReceiverModuleForRaw($receiver)
    {
        $respReceiver = $this->fmtReceiverForRaw($receiver);

        $dispatch = [];
        foreach ($receiver['dispatch'] ?? [] as $item) {
            $this->fmtConditionsForRaw($item);
            $item['receiver'] = $this->fmtReceiverForRaw($item['receiver']);
            $dispatch[] = $item;
        }
        $respReceiver['dispatch'] = $dispatch;
        $respReceiver['mode'] = $receiver['mode'] ?? self::RECV_DISPATCH_MODE_LAZY;

        return $respReceiver;
    }

    /**
     * 格式化workflow-原始信息.
     * @param mixed $task
     * @param mixed $workflow
     */
    protected function fmtWorkflowForRaw($task, $workflow)
    {
        $workflow['enable'] = (bool) $task->enable_workflow;

        if ($workflow['enable']) {
            $reminds = [];
            foreach ($workflow['reminds'] as $remind) {
                if (! $remind['reuse_receiver']) {
                    $remind['receiver'] = $this->fmtReceiverForRaw($remind['receiver']);
                }
                $reminds[] = $remind;
            }
            $workflow['reminds'] = $reminds;
        }

        return $workflow;
    }

    protected function fmtFilterForRaw($task, $filter)
    {
        $filter['enable'] = (bool) $task->enable_filter;
        $this->fmtConditionsForRaw($filter);
        return $filter;
    }

    protected function fmtUpgradeForRaw($task, $upgrade)
    {
        $upgrade['enable'] = (bool) $task->enable_upgrade;

        if ($upgrade['enable']) {
            $strategies = [];
            foreach ($upgrade['strategies'] as $strategy) {
                if (! $strategy['reuse_receiver']) {
                    $strategy['receiver'] = $this->fmtReceiverForRaw($strategy['receiver']);
                }
                $strategies[] = $strategy;
            }
            $upgrade['strategies'] = $strategies;
        }

        return $upgrade;
    }

    protected function fmtCompressForRaw($task, $compress)
    {
        $compress['enable'] = (bool) $task->enable_compress;
        $this->fmtConditionsForRaw($compress);
        return $compress;
    }

    protected function fmtRecoverForRaw($task, $recovery)
    {
        $recovery['enable'] = (bool) $task->enable_recovery;
        $this->fmtConditionsForRaw($receiver);
        return $recovery;
    }

    protected function fmtTemplateForRaw($template, $templateId)
    {
        $data = [
            'template_id' => $templateId ? (int) $templateId : null,
        ];
        // 载入预定义模板信息
        if ($data['template_id']) {
            // 预定义不用返回模板信息
        } else {
            foreach ($template as $scene => $sceneChannels) {
                foreach ($sceneChannels as $channel => $channelTemplate) {
                    $data['udf'][$scene][$channel] = $channelTemplate;
                }
            }
        }

        return $data;
    }

    protected function fmtConditionsForRaw(&$data)
    {
        // 判断conditions为空时
        if (empty($data['conditions']) || ! is_array($data['conditions'])) {
            $data['conditions'] = [];
            return;
        }

        $conditions = [];
        foreach ($data['conditions'] as $items) {
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
        $data['conditions'] = $conditions;
    }

    /**
     * 验证权限参数.
     *
     * @param array $param
     * @param array $user 如果为新增，$user必填
     * @param mixed $isCreate
     * @return array
     */
    private function validAndFormatPermission($param, $user = null, $isCreate = false)
    {
        $users = [];
        $permission = [
            'rw' => $isCreate ? [$user['uid']] : [],
            'ro' => [],
        ];
        if (! empty($param)) {
            foreach (['rw', 'ro'] as $type) {
                if (! empty($param[$type])) {
                    foreach ($param[$type] as $uid) {
                        if (! is_numeric($uid)) {
                            throw new AppException('用户UID必须为数字', [
                                'uid' => $uid,
                            ]);
                        }
                        $permission[$type][] = $uid;
                        $users[] = $uid;
                    }
                    $permission[$type] = array_unique($permission[$type]);
                }
            }
        }

        // 读写权限不能为空
        if (empty($permission['rw'])) {
            throw new AppException('read_write permission cannot be empty');
        }

        return $permission;
    }

    private function validAndFormatWorkflow($params)
    {
        // 未开启直接退出
        if (empty($params['enable'])) {
            return;
        }
        $workflow = [];
        // 升级通知告警组集合，存到表里做反查使用
        $alarmGroups = [];

        // 工作流提醒配置
        $reminds = [];
        if (empty($params['reminds']) || ! is_array($params['reminds'])) {
            $workflow['reminds'] = $reminds;
            return [$workflow, $alarmGroups];
        }
        foreach ($params['reminds'] as $item) {
            if (empty($item['mode']) || ! in_array($item['mode'], ['once', 'cycle'])) {
                throw new AppException('workflow.reminds.*.mode invalid');
            }
            if (empty($item['interval']) || ! is_numeric($item['interval']) || $item['interval'] <= 0) {
                throw new AppException('workflow.reminds.*.interval invalid');
            }
            if (! isset($item['status']) || ! Workflow::isProcessable($item['status'])) {
                throw new AppException('workflow.reminds.*.status invalid');
            }
            $remind = [
                'interval' => (int) $item['interval'],
                'status' => (int) $item['status'],
                'mode' => $item['mode'],
            ];
            if (empty($item['reuse_receiver'])) {
                // 没复用告警通知人
                $remind['reuse_receiver'] = 0;
                $receiver = $this->validAndFormatReceiver($item['receiver'] ?? [], 'workflow');
                $remind['receiver'] = $receiver;
                if ($receiver['alarmgroup']) {
                    $alarmGroups += $receiver['alarmgroup'];
                }
            } else {
                // 复用了告警通知人
                $remind['reuse_receiver'] = 1;
            }

            $reminds[] = $remind;
        }
        $workflow['reminds'] = $reminds;

        return ['workflow' => $workflow, 'alarmgroup' => $alarmGroups];
    }

    private function validAndFormatFilter($params)
    {
        // 未开启直接退出
        if (empty($params['enable'])) {
            return;
        }
        $filter = [];

        // 未命中过滤
        $filter['not_match'] = empty($params['not_match']) ? 0 : 1;

        // 过滤模式
        if (empty($params['mode']) || ! in_array($params['mode'], [1, 2])) {
            throw new AppException('filter.mode invalid');
        }
        $filter['mode'] = (int) $params['mode'];

        // 过滤条件
        $filter['conditions'] = $this->validAndFormatCondition($params, 'filter', function ($items, &$rules) {
            if (! isset($items['level']) || ! AlarmTask::hasAlarmLevel($items['level'], true)) {
                throw new AppException('filter.conditions.*.level invalid');
            }
            $rules['level'] = (int) $items['level'];
        });

        return $filter;
    }

    private function validAndFormatUpgrade($params)
    {
        // 未开启直接退出
        if (empty($params['enable'])) {
            return;
        }
        $upgrade = [];
        // 升级通知告警组集合，存到表里做反查使用
        $alarmGroups = [];

        // 升级策略
        $strategies = [];
        if (empty($params['strategies']) || ! is_array($params['strategies'])) {
            $upgrade['strategies'] = $strategies;
            return [$upgrade, $alarmGroups];
        }
        foreach ($params['strategies'] as $item) {
            if (empty($item['interval']) || ! is_numeric($item['interval']) || $item['interval'] <= 0) {
                throw new AppException('upgrade.strategies.*.interval invalid');
            }
            if (empty($item['count']) || ! is_numeric($item['count']) || $item['count'] <= 0) {
                throw new AppException('upgrade.strategies.*.count invalid');
            }
            if (! isset($item['level']) || ! AlarmTask::hasAlarmLevel($item['level'])) {
                throw new AppException('upgrade.strategies.*.level invalid');
            }
            $strategy = [
                'interval' => (int) $item['interval'],
                'count' => (int) $item['count'],
                'level' => (int) $item['level'],
            ];
            if (empty($item['reuse_receiver'])) {
                // 没复用告警通知人
                $strategy['reuse_receiver'] = 0;
                $receiver = $this->validAndFormatReceiver($item['receiver'] ?? [], 'upgrade');
                $strategy['receiver'] = $receiver;
                if ($receiver['alarmgroup']) {
                    $alarmGroups += $receiver['alarmgroup'];
                }
            } else {
                // 复用了告警通知人
                $strategy['reuse_receiver'] = 1;
            }

            $strategies[] = $strategy;
        }
        $upgrade['strategies'] = $strategies;

        return ['upgrade' => $upgrade, 'alarmgroup' => $alarmGroups];
    }

    /**
     * @param array $params
     * @param string $scene 使用场景，包括告警通知人、告警升级、告警工作流
     * @return array
     */
    private function validAndFormatReceiver($params, $scene = null)
    {
        // 场景描述，用于抛异常明确位置
        $sceneDesc = $scene ? ' on ' . $scene : '';
        if (empty($params)) {
            throw new AppException('receiver is required' . $sceneDesc);
        }

        // 判断报警组
        $alarmGroups = [];
        if (! empty($params['alarmgroup']) && is_array($params['alarmgroup'])) {
            $alarmGroups = array_unique($params['alarmgroup']);
            if (AlarmGroup::whereIn('id', $alarmGroups)->count() != count($alarmGroups)) {
                throw new AppException('receiver alarmgroup invalid' . $sceneDesc);
            }
        }
        // 判断自定义通知渠道
        $channels = $this->alarmGroup->validAndFormatChannels($params, $sceneDesc);

        // 告警组和自定义通知渠道不能都为空
        if (! $alarmGroups && ! $channels) {
            throw new AppException('alarmgroup and channels cannot be empty together' . $sceneDesc);
        }

        return ['channels' => $channels, 'alarmgroup' => $alarmGroups];
    }

    /**
     * 验证并格式化告警通知人模块，包括分级告警等后期扩展功能.
     *
     * @param array $params receiver字段的信息
     * @return array
     */
    private function validAndFormatReceiverModule($params)
    {
        $receiver = $this->validAndFormatReceiver($params, 'receiver');

        $dispatch = [];
        foreach ($params['dispatch'] ?? [] as $item) {
            $dispatch[] = [
                'conditions' => $this->validAndFormatCondition($item, 'receiver-dispatch'),
                'receiver' => $this->validAndFormatReceiver($item['receiver'], 'receiver-dispatch'),
            ];
        }
        $receiver['dispatch'] = $dispatch;

        // 验证mode参数
        if (! empty($params['mode']) && ! isset(self::$recvDispatchModes[$params['mode']])) {
            throw new AppException('invalid receiver-dispatch mode', [
                'mode' => $params['mode'],
            ]);
        }
        $receiver['mode'] = $params['mode'] ?? self::RECV_DISPATCH_MODE_LAZY;

        return $receiver;
    }

    private function validAndFormatCompress($params)
    {
        // 未开启直接退出
        if (empty($params['enable'])) {
            return;
        }
        $compress = [];

        // 收敛方式
        if (empty($params['method']) || ! is_numeric($params['method'])) {
            throw new AppException('compress.method invalid');
        }
        switch ($params['method']) {
            case AlarmTask::COMPRESS_METHOD_CONDITION:
                $compress['conditions'] = $this->validAndFormatCondition($params, 'compress');
                break;
            case AlarmTask::COMPRESS_METHOD_CONTENT:
                // do nothing
                break;
            case AlarmTask::COMPRESS_METHOD_FULL:
                // do nothing
                break;
            default:
                throw new AppException("not support compress.method [{$params['method']}]", [
                    'method' => $params['method'],
                ]);
        }
        $compress['method'] = (int) $params['method'];

        // 收敛策略
        if (empty($params['strategy']) || ! is_numeric($params['strategy'])) {
            throw new AppException('compress.strategy invalid');
        }
        if (! AlarmTask::hasCompressStrategy($params['strategy'])) {
            throw new AppException("not support compress.strategy [{$params['strategy']}]", [
                'strategy' => $params['strategy'],
            ]);
        }
        $compress['strategy'] = (int) $params['strategy'];

        // 周期时间
        if (empty($params['strategy_cycle']) || ! is_numeric($params['strategy_cycle'])) {
            throw new AppException('compress.strategy_cycle invalid');
        }
        $compress['strategy_cycle'] = (int) $params['strategy_cycle'];
        // 周期次数
        if (
            in_array($params['strategy'], [
                AlarmTask::COMPRESS_STRATEGY_CYCLE_TIMES,
                AlarmTask::COMPRESS_STRATEGY_TIMES_CYCLE,
                AlarmTask::COMPRESS_STRATEGY_TIMES,
            ])
        ) {
            if (empty($params['strategy_count']) || ! is_numeric($params['strategy_count'])) {
                throw new AppException('compress.strategy_count invalid');
            }
            $compress['strategy_count'] = (int) $params['strategy_count'];
        }

        // 未命中收敛
        $compress['not_match'] = empty($params['not_match']) ? 0 : 1;

        return $compress;
    }

    private function validAndFormatCondition($params, $scene = null, $handleOtherRulesField = null)
    {
        // 场景描述，用于抛异常明确位置
        $sceneDesc = $scene ? ' on ' . $scene : '';

        // 判断conditions不能为空
        if (empty($params['conditions']) || ! is_array($params['conditions'])) {
            throw new AppException('conditions cannot be empty' . $sceneDesc);
        }

        $conditions = [];
        foreach ($params['conditions'] as $items) {
            if (empty($items['rule']) || ! is_array($items['rule'])) {
                continue;
            }
            $rules = ['rule' => []];
            // 用于处理rule以外的其他字段，不使用call_user_func是为了让地址引用参数可用
            is_callable($handleOtherRulesField) && $handleOtherRulesField($items, $rules);

            foreach ($items['rule'] as $item) {
                if (empty($item['field'])) {
                    throw new AppException('conditions.*.rule.field cannot be empty' . $sceneDesc);
                }
                if (! AlarmTask::hasCondOperator($item['operator'])) {
                    throw new AppException("condition operator [{$item['operator']}] invalid" . $sceneDesc, [
                        'scene' => $scene,
                        'operator' => $item['operator'],
                    ]);
                }
                $rule = [
                    'field' => $item['field'],
                    'field_split' => ConditionArr::fieldSplit($item['field']),
                    'operator' => $item['operator'],
                ];
                if (! in_array($item['operator'], AlarmTask::$noThresholdOperators)) {
                    // 要求阈值的操作符，此处不要用empty，可能误杀为0的值
                    if (! isset($item['threshold']) || $item['threshold'] === '') {
                        throw new AppException("condition operator [{$item['operator']}]`s threshold cannot be empty" . $sceneDesc);
                    }
                    // 如果阈值要求拆分，则拆分为数组
                    if (in_array($item['operator'], AlarmTask::$explodeThresholdOperators)) {
                        $rule['threshold'] = array_unique(explode(AlarmTask::$explodeThresholdSymbol, $item['threshold']));
                    } else {
                        $rule['threshold'] = $item['threshold'];
                    }
                }
                $rules['rule'][] = $rule;
            }
            $conditions[] = $rules;
        }
        // 判断conditions里面的规则不能为空，不能省略
        if (empty($conditions)) {
            throw new AppException('conditions cannot be empty' . $sceneDesc);
        }

        return $conditions;
    }

    private function validAndFormatRecovery($params)
    {
        // 未开启直接退出
        if (empty($params['enable'])) {
            return;
        }
        $recovery = [];

        // 恢复方式
        if (empty($params['mode']) || ! is_numeric($params['mode'])) {
            throw new AppException('recovery.mode invalid');
        }
        switch ($params['mode']) {
            case AlarmTask::RECOVERY_MODE_CONDITION:
                $recovery['conditions'] = $this->validAndFormatCondition($params, 'recovery');
                break;
            case AlarmTask::RECOVERY_MODE_DELAY:
                if (empty($params['delay_interval']) || ! is_numeric($params['delay_interval'])) {
                    throw new AppException('recovery.delay_interval invalid');
                }
                $recovery['delay_interval'] = (int) $params['delay_interval'];
                break;
            default:
                throw new AppException("not support recovery.mode [{$params['mode']}] type");
        }
        $recovery['mode'] = (int) $params['mode'];

        return $recovery;
    }
}
