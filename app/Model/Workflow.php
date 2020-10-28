<?php

declare(strict_types=1);

namespace App\Model;

use App\Consts\Noticer;
use App\Exception\AppException;
use App\Exception\ForbiddenException;
use App\Support\ExceptionHandler;
use App\Support\MySQL;
use Dog\Noticer\Channel\DingGroup;
use Dog\Noticer\Channel\DingGroup\MsgType\Text as DingGroupText;
use Dog\Noticer\Channel\DingWorker;
use Dog\Noticer\Channel\DingWorker\MsgType\Text as DingWorkerText;
use Dog\Noticer\Channel\Email;
use Dog\Noticer\Channel\Phone;
use Dog\Noticer\Channel\Sms;
use Dog\Noticer\Channel\YachGroup;
use Dog\Noticer\Channel\YachGroup\MsgType\Text as YachGroupText;
use Dog\Noticer\Channel\YachWorker;
use Dog\Noticer\Channel\YachWorker\MsgType\Text as YachWorkerText;
use Dog\Noticer\Component\Guzzle;
use Dog\Noticer\Exception\NoticeException;
use GuzzleHttp\Client as GuzzleHttpClient;
use Hyperf\Database\Model\Collection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Parallel;
use stdClass;
use Throwable;

class Workflow extends Model
{
    /**
     * 工作流状态
     */
    // 待处理
    public const STATUS_PENDING = 0;

    // 处理中
    public const STATUS_PROCESSING = 1;

    // 处理完成
    public const STATUS_PROCESSED = 2;

    // 关闭
    public const STATUS_CLOSED = 9;

    /**
     * 工作流pipeline非标准状态
     */
    // 提醒
    public const STATUS_REMIND = 3;

    // 指派
    public const STATUS_ASSIGN = 4;

    // 重新激活
    public const STATUS_REACTIVE = 5;

    /**
     * 通知模板
     */
    // 生成告警任务
    public const SCENE_GENERATED = 'generated';

    // 认领
    public const SCENE_CLAIM = 'claim';

    // 指派
    public const SCENE_ASSIGN = 'assign';

    // 处理完成
    public const SCENE_PROCESSED = 'processed';

    // 重新激活
    public const SCENE_REACTIVE = 'reactive';

    // 关闭
    public const SCENE_CLOSE = 'close';

    // 提醒-待处理
    public const SCENE_REMIND_PENDING = 'remind_pending';

    // 提醒-处理中
    public const SCENE_REMIND_PROCESSING = 'remind_processing';

    public $timestamps = false;

    // 可用状态
    public static $availableStatuses = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_PROCESSING => '处理中',
        self::STATUS_PROCESSED => '处理完成',
        self::STATUS_CLOSED => '关闭',
    ];

    /**
     * Webhook场景映射关系.
     *
     * @var array
     */
    public static $webhookSceneMap = [
        self::SCENE_GENERATED => Noticer::SCENE_WORKFLOW_GENERATED,
        self::SCENE_CLAIM => Noticer::SCENE_WORKFLOW_CLAIM,
        self::SCENE_ASSIGN => Noticer::SCENE_WORKFLOW_ASSIGN,
        self::SCENE_PROCESSED => Noticer::SCENE_WORKFLOW_PROCESSED,
        self::SCENE_REACTIVE => Noticer::SCENE_WORKFLOW_REACTIVE,
        self::SCENE_CLOSE => Noticer::SCENE_WORKFLOW_CLOSE,
        self::SCENE_REMIND_PENDING => Noticer::SCENE_WORKFLOW_REMIND_PENDING,
        self::SCENE_REMIND_PROCESSING => Noticer::SCENE_WORKFLOW_REMIND_PROCESSING,
    ];

    protected $table = 'workflow';

    protected $fillable = ['task_id', 'metric', 'history_id', 'status', 'created_at', 'updated_at'];

    /**
     * @Inject
     * @var AlarmTaskPermission
     */
    protected $alarmTaskPermission;

    /**
     * 是否是可处理状态
     *
     * @param int $status
     * @return bool
     */
    public static function isProcessable($status)
    {
        return in_array($status, [
            static::STATUS_PENDING,
            static::STATUS_PROCESSING,
        ]);
    }

    /**
     * 告警任务列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param null|mixed $search
     * @param mixed $order
     * @param mixed $timerange
     * @param null|mixed $status
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     * @param null|mixed $tagId
     * @param null|mixed $user
     */
    public function list(
        $page = 1,
        $pageSize = 20,
        $search = null,
        $order = [],
        $timerange = [],
        $status = null,
        $departmentId = null,
        $taskId = null,
        $tagId = null,
        $user = null
    ) {
        if ($search) {
            // TODO 从ES搜索
            return $this->listFromMysql($page, $pageSize, $order, $timerange, $status, $departmentId, $taskId, $tagId, $user);
        }
        // 从MySQL获取列表
        return $this->listFromMysql($page, $pageSize, $order, $timerange, $status, $departmentId, $taskId, $tagId, $user);
    }

    /**
     * 从MySQL获取列表.
     * @param mixed $page
     * @param mixed $pageSize
     * @param mixed $order
     * @param mixed $timerange
     * @param null|mixed $status
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     * @param null|mixed $tagId
     * @param null|mixed $user
     */
    public function listFromMysql(
        $page = 1,
        $pageSize = 20,
        $order = [],
        $timerange = [],
        $status = null,
        $departmentId = null,
        $taskId = null,
        $tagId = null,
        $user = null
    ) {
        if ($user) {
            $userTaskIds = $this->alarmTaskPermission->getTaskIdByUid($user['uid']);
        }

        $builder = $this->with('task', 'task.department', 'history');
        if ($taskId) {
            $builder->where('task_id', $taskId);
        }

        if ($tagId) {
            $tasksIdBytagId = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            if ($user && ! $user->isAdmin()) {
                $tasksIdBytagId = array_intersect($userTaskIds, $tasksIdBytagId);
            }
            $builder->whereIn('task_id', $tasksIdBytagId);
        }

        if ($status !== null) {
            $builder->where('status', $status);
        }
        if ($departmentId) {
            // 查询出所有taskId，然后where in
            $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
            if ($user && ! $user->isAdmin()) {
                $taskIds = array_intersect($userTaskIds, $taskIds);
            }
            $builder->whereIn('task_id', $taskIds);
        }

        // 权限判断
        if ($user && ! $user->isAdmin() && ! $taskId && ! $departmentId && ! $tagId) {
            $builder->whereIn('task_id', $userTaskIds);
        }

        if ($timerange) {
            MySQL::whereTime($builder, $timerange, 'created_at');
        }

        MySQL::builderSort($builder, $order);

        return MySQL::jsonPaginate($builder, $page, $pageSize);
    }

    public function getByIdAndThrow($workflowId, $throwable = false)
    {
        $workflow = $this->where('id', $workflowId)->first();
        if ($throwable && empty($workflow)) {
            throw new AppException("workflow [{$workflowId}] not found");
        }

        return $workflow;
    }

    /**
     * 详情.
     * @param mixed $workflowId
     * @param null|mixed $user
     */
    public function showWorkflow($workflowId, $user = null)
    {
        $workflow = $this->getByIdAndThrow($workflowId, true);
        $workflow->load('task', 'task.department', 'history');
        if ($user) {
            $this->validatePermission($workflow['task_id'], $user);
        }
        $workflow->pipelines = make(WorkflowPipeline::class)->pipelines($workflowId);
        return $workflow;
    }

    /**
     * 统计各状态数量.
     * @param null|mixed $departmentId
     * @param null|mixed $taskId
     * @param mixed $timerange
     * @param null|mixed $tagId
     * @param null|mixed $user
     */
    public function statsByStatus(
        $departmentId = null,
        $taskId = null,
        $timerange = [],
        $tagId = null,
        $user = null
    ) {
        if ($user) {
            $userTaskIds = $this->alarmTaskPermission->getTaskIdByUid($user['uid']);
        }

        $builder = $this->select('status', Db::raw('COUNT(*) AS `count`'));
        if ($taskId) {
            $builder->where('task_id', $taskId);
        }

        if ($tagId) {
            $tasksIdBytagId = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            if ($user && ! $user->isAdmin()) {
                $tasksIdBytagId = array_intersect($userTaskIds, $tasksIdBytagId);
            }
            $builder->whereIn('task_id', $tasksIdBytagId);
        }

        if ($departmentId) {
            // 查询出所有taskId，然后where in
            $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
            if ($user && ! $user->isAdmin()) {
                $taskIds = array_intersect($userTaskIds, $taskIds);
            }
            $builder->whereIn('task_id', $taskIds);
        }

        // 权限判断
        if ($user && ! $user->isAdmin() && ! $taskId && ! $departmentId && ! $tagId) {
            $builder->whereIn('task_id', $userTaskIds);
        }

        if ($timerange) {
            MySQL::whereTime($builder, $timerange, 'created_at');
        }
        $statsData = $builder->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 补充0
        foreach (static::$availableStatuses as $availableStatus => $statusTitle) {
            if (! isset($statsData[$availableStatus])) {
                $statsData[$availableStatus] = 0;
            }
        }

        return $statsData;
    }

    /**
     * 认领.
     * @param mixed $workflowIds
     * @param mixed $remark
     * @param null|mixed $user
     */
    public function claim($workflowIds, $remark, $user = null)
    {
        $workflows = $this->getWorkflowsAndThrow($workflowIds);
        if ($user) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $this->validatePermission($taskIds, $user);
        }
        $time = time();
        // 通知变量
        $noticeVars = [];
        // 判断状态是否合法，如果合法，设置插入数据
        $insertPipelines = [];
        foreach ($workflows as $workflow) {
            if ($workflow['status'] != Workflow::STATUS_PENDING) {
                throw new AppException(
                    sprintf(
                        'Only workflow is pending that can be claimed, but workflow [%s] is [%s]',
                        $workflow['id'],
                        $workflow['status']
                    )
                );
            }
            $insertPipelines[] = [
                'task_id' => $workflow['task_id'],
                'workflow_id' => $workflow['id'],
                'status' => Workflow::STATUS_PROCESSING,
                'remark' => $remark,
                'props' => '{}', // 空对象
                'created_by' => $user['uid'],
                'created_at' => $time,
            ];

            $workflowArr = $workflow->toArray();
            $workflowArr['created_at'] = date('Y-m-d H:i:s', $workflowArr['created_at']);
            $workflowArr['status'] = static::$availableStatuses[$workflowArr['status']];
            $task = $workflowArr['task'];
            $history = $workflowArr['history'];
            $history['ctn'] = json_decode($history['ctn'], true);
            $history['level'] = AlarmHistory::$levelsNoExtend[$history['level']];
            unset($workflowArr['task'], $workflowArr['history']);
            // 通知变量
            $noticeVars[] = [
                'workflow' => $workflowArr,
                'task' => $task,
                'history' => $history,
                'pipeline' => [
                    'remark' => $remark,
                    'created_at' => date('Y-m-d H:i:s', $time),
                    'user' => [
                        'uid' => $user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'user' => explode('@', $user['email'])[0],
                    ],
                ],
            ];
        }

        $ret = [];
        // 写入数据
        Db::beginTransaction();
        try {
            // 写入Pipelines
            Db::table('workflow_pipeline')->insert($insertPipelines);
            // 更新工作流状态
            foreach ($workflows as $workflow) {
                $workflow->status = Workflow::STATUS_PROCESSING;
                $workflow->updated_at = time();
                $workflow->save();

                $ret[] = [
                    'id' => $workflow['id'],
                    'status' => $workflow['status'],
                ];
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 发送告警通知
        $errors = $this->sendPipelineNotice($noticeVars, Workflow::SCENE_CLAIM);

        // 修改delay_queue的队列
        $this->setDelayQueue($workflows, Workflow::STATUS_PENDING, Workflow::STATUS_PROCESSING);

        return [$ret, $errors];
    }

    /**
     * 处理完成.
     * @param mixed $workflowIds
     * @param mixed $remark
     * @param null|mixed $user
     */
    public function processed($workflowIds, $remark, $user = null)
    {
        $workflows = $this->getWorkflowsAndThrow($workflowIds);
        if ($user) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $this->validatePermission($taskIds, $user);
        }
        $time = time();
        // 通知变量
        $noticeVars = [];
        // 判断状态是否合法，如果合法，设置插入数据
        $insertPipelines = [];
        foreach ($workflows as $workflow) {
            if ($workflow['status'] != Workflow::STATUS_PROCESSING) {
                throw new AppException(
                    sprintf(
                        'Only workflow is processing that can be processed, but workflow [%s] is [%s]',
                        $workflow['id'],
                        $workflow['status']
                    )
                );
            }
            $insertPipelines[] = [
                'task_id' => $workflow['task_id'],
                'workflow_id' => $workflow['id'],
                'status' => Workflow::STATUS_PROCESSED,
                'remark' => $remark,
                'props' => '{}', // 空对象
                'created_by' => $user['uid'],
                'created_at' => $time,
            ];

            $workflowArr = $workflow->toArray();
            $workflowArr['created_at'] = date('Y-m-d H:i:s', $workflowArr['created_at']);
            $workflowArr['status'] = static::$availableStatuses[$workflowArr['status']];
            $task = $workflowArr['task'];
            $history = $workflowArr['history'];
            $history['ctn'] = json_decode($history['ctn'], true);
            $history['level'] = AlarmHistory::$levelsNoExtend[$history['level']];
            unset($workflowArr['task'], $workflowArr['history']);
            // 通知变量
            $noticeVars[] = [
                'workflow' => $workflowArr,
                'task' => $task,
                'history' => $history,
                'pipeline' => [
                    'remark' => $remark,
                    'created_at' => date('Y-m-d H:i:s', $time),
                    'user' => [
                        'uid' => $user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'user' => explode('@', $user['email'])[0],
                    ],
                ],
            ];
        }

        $ret = [];
        // 写入数据
        Db::beginTransaction();
        try {
            // 写入Pipelines
            Db::table('workflow_pipeline')->insert($insertPipelines);
            // 更新工作流状态
            foreach ($workflows as $workflow) {
                $workflow->status = Workflow::STATUS_PROCESSED;
                $workflow->updated_at = time();
                $workflow->save();

                $ret[] = [
                    'id' => $workflow['id'],
                    'status' => $workflow['status'],
                ];
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 发送告警通知
        $errors = $this->sendPipelineNotice($noticeVars, Workflow::SCENE_PROCESSED);

        // 修改delay_queue的队列
        $this->setDelayQueue($workflows, Workflow::STATUS_PROCESSING);

        return [$ret, $errors];
    }

    /**
     * 关闭.
     * @param mixed $workflowIds
     * @param mixed $remark
     * @param null|mixed $user
     */
    public function close($workflowIds, $remark, $user = null)
    {
        $workflows = $this->getWorkflowsAndThrow($workflowIds);
        if ($user) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $this->validatePermission($taskIds, $user);
        }
        $time = time();
        // 通知变量
        $noticeVars = [];
        // 判断状态是否合法，如果合法，设置插入数据
        $insertPipelines = [];
        foreach ($workflows as $workflow) {
            if (! in_array($workflow['status'], [Workflow::STATUS_PROCESSING, Workflow::STATUS_PENDING])) {
                throw new AppException(
                    sprintf(
                        'Only workflow is pending or processing that can be closed, but workflow [%s] is [%s]',
                        $workflow['id'],
                        $workflow['status']
                    )
                );
            }
            $insertPipelines[] = [
                'task_id' => $workflow['task_id'],
                'workflow_id' => $workflow['id'],
                'status' => Workflow::STATUS_CLOSED,
                'remark' => $remark,
                'props' => '{}', // 空对象
                'created_by' => $user['uid'],
                'created_at' => $time,
            ];

            $workflowArr = $workflow->toArray();
            $workflowArr['created_at'] = date('Y-m-d H:i:s', $workflowArr['created_at']);
            $workflowArr['status'] = static::$availableStatuses[$workflowArr['status']];
            $task = $workflowArr['task'];
            $history = $workflowArr['history'];
            $history['ctn'] = json_decode($history['ctn'], true);
            $history['level'] = AlarmHistory::$levelsNoExtend[$history['level']];
            unset($workflowArr['task'], $workflowArr['history']);
            // 通知变量
            $noticeVars[] = [
                'workflow' => $workflowArr,
                'task' => $task,
                'history' => $history,
                'pipeline' => [
                    'remark' => $remark,
                    'created_at' => date('Y-m-d H:i:s', $time),
                    'user' => [
                        'uid' => $user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'user' => explode('@', $user['email'])[0],
                    ],
                ],
            ];
        }

        $ret = [];
        // 写入数据
        Db::beginTransaction();
        try {
            // 写入Pipelines
            Db::table('workflow_pipeline')->insert($insertPipelines);
            // 更新工作流状态
            foreach ($workflows as $workflow) {
                $workflow->status = Workflow::STATUS_CLOSED;
                $workflow->updated_at = time();
                $workflow->save();

                $ret[] = [
                    'id' => $workflow['id'],
                    'status' => $workflow['status'],
                ];
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 发送告警通知
        $errors = $this->sendPipelineNotice($noticeVars, Workflow::SCENE_CLOSE);

        // 修改delay_queue的队列
        DelayQueueWorkflow::whereIn('workflow_id', $workflowIds)->delete();

        return [$ret, $errors];
    }

    /**
     * 重新激活.
     * @param mixed $workflowIds
     * @param mixed $remark
     * @param mixed $user
     */
    public function reactive($workflowIds, $remark, $user)
    {
        $workflows = $this->getWorkflowsAndThrow($workflowIds);
        if ($user) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $this->validatePermission($taskIds, $user);
        }
        $time = time();
        // 通知变量
        $noticeVars = [];
        // 判断状态是否合法，如果合法，设置插入数据
        $insertPipelines = [];
        foreach ($workflows as $workflow) {
            if ($workflow['status'] != Workflow::STATUS_CLOSED) {
                throw new AppException(
                    sprintf(
                        'Only workflow is closed that can be reactive, but workflow [%s] is [%s]',
                        $workflow['id'],
                        $workflow['status']
                    )
                );
            }
            $insertPipelines[] = [
                'task_id' => $workflow['task_id'],
                'workflow_id' => $workflow['id'],
                'status' => Workflow::STATUS_REACTIVE,
                'remark' => $remark,
                'props' => '{}', // 空对象
                'created_by' => $user['uid'],
                'created_at' => time(),
            ];

            $workflowArr = $workflow->toArray();
            $workflowArr['created_at'] = date('Y-m-d H:i:s', $workflowArr['created_at']);
            $workflowArr['status'] = static::$availableStatuses[$workflowArr['status']];
            $task = $workflowArr['task'];
            $history = $workflowArr['history'];
            $history['ctn'] = json_decode($history['ctn'], true);
            $history['level'] = AlarmHistory::$levelsNoExtend[$history['level']];
            unset($workflowArr['task'], $workflowArr['history']);
            // 通知变量
            $noticeVars[] = [
                'workflow' => $workflowArr,
                'task' => $task,
                'history' => $history,
                'pipeline' => [
                    'remark' => $remark,
                    'created_at' => date('Y-m-d H:i:s', $time),
                    'user' => [
                        'uid' => $user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'user' => explode('@', $user['email'])[0],
                    ],
                ],
            ];
        }

        $ret = [];
        // 写入数据
        Db::beginTransaction();
        try {
            // 写入Pipelines
            Db::table('workflow_pipeline')->insert($insertPipelines);
            // 更新工作流状态
            foreach ($workflows as $workflow) {
                $workflow->status = Workflow::STATUS_PENDING;
                $workflow->updated_at = time();
                $workflow->save();

                $ret[] = [
                    'id' => $workflow['id'],
                    'status' => $workflow['status'],
                ];
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 发送告警通知
        $errors = $this->sendPipelineNotice($noticeVars, Workflow::SCENE_REACTIVE);

        // 修改delay_queue的队列
        $this->setDelayQueue($workflows, null, Workflow::STATUS_PENDING);

        return [$ret, $errors];
    }

    /**
     * 重新激活.
     * @param mixed $workflowIds
     * @param mixed $remark
     * @param mixed $assignTo
     * @param mixed $user
     */
    public function assign($workflowIds, $remark, $assignTo, $user)
    {
        $workflows = $this->getWorkflowsAndThrow($workflowIds);
        if ($user) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $this->validatePermission($taskIds, $user);
        }
        // 判断被指派用户是否合法
        $users = User::whereIn('uid', $assignTo)
            ->select('uid', 'username', 'email', 'department')
            ->get();

        // 判断是否users都存在
        if (count($assignTo) != $users->count()) {
            $notExists = implode(', ', array_diff($assignTo, $users->pluck('uid')->toArray()));
            throw new AppException("assignTo users [{$notExists}] not exists");
        }

        $time = time();
        // 通知变量
        $noticeVars = [];
        $assignToUsers = [];
        foreach ($users as $item) {
            $assignToUsers[] = $item['username'] . '(' . explode('@', $item['email'])[0] . ')';
        }
        $assignToUsers = implode('、', $assignToUsers);

        // 判断状态是否合法，如果合法，设置插入数据
        $insertPipelines = [];
        foreach ($workflows as $workflow) {
            if (! in_array($workflow['status'], [Workflow::STATUS_PROCESSING, Workflow::STATUS_PENDING])) {
                throw new AppException(
                    sprintf(
                        'Only workflow is pending or processing that can be assign, but workflow [%s] is [%s]',
                        $workflow['id'],
                        $workflow['status']
                    )
                );
            }
            $insertPipelines[] = [
                'task_id' => $workflow['task_id'],
                'workflow_id' => $workflow['id'],
                'status' => Workflow::STATUS_ASSIGN,
                'remark' => $remark,
                'props' => json_encode(['assignto' => $assignTo]),
                'created_by' => $user['uid'],
                'created_at' => time(),
            ];

            $workflowArr = $workflow->toArray();
            $workflowArr['created_at'] = date('Y-m-d H:i:s', $workflowArr['created_at']);
            $workflowArr['status'] = static::$availableStatuses[$workflowArr['status']];
            $task = $workflowArr['task'];
            $history = $workflowArr['history'];
            $history['ctn'] = json_decode($history['ctn'], true);
            $history['level'] = AlarmHistory::$levelsNoExtend[$history['level']];
            unset($workflowArr['task'], $workflowArr['history']);
            // 通知变量
            $noticeVars[] = [
                'workflow' => $workflowArr,
                'task' => $task,
                'history' => $history,
                'pipeline' => [
                    'remark' => $remark,
                    'created_at' => date('Y-m-d H:i:s', $time),
                    'user' => [
                        'uid' => $user['uid'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'user' => explode('@', $user['email'])[0],
                    ],
                    'props' => [
                        'assigntoUsers' => $assignToUsers,
                    ],
                ],
            ];
        }

        // 写入数据
        Db::beginTransaction();
        try {
            // 写入Pipelines
            Db::table('workflow_pipeline')->insert($insertPipelines);
            // 更新工作流状态
            foreach ($workflows as $workflow) {
                // 指派之后状态不变
                $workflow->updated_at = time();
                $workflow->save();
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }

        // 发送告警通知
        // TODO 区分给不同人发送，指派人单独发送
        $errors = $this->sendPipelineNotice($noticeVars, Workflow::SCENE_ASSIGN);

        // 无状态变更，返回空
        return [[], $errors];
    }

    public function task()
    {
        return $this->hasOne(AlarmTask::class, 'id', 'task_id')->select('id', 'name', 'department_id');
    }

    public function history()
    {
        return $this->hasOne(AlarmHistory::class, 'id', 'history_id')
            ->select('id', 'uuid', 'batch', 'metric', 'notice_time', 'level', 'ctn', 'created_at');
    }

    /**
     * 获取告警接收人.
     * @param mixed $taskId
     * @param mixed $history
     */
    public function getReceiver($taskId, $history)
    {
        // 通知人列表
        $receiver = [];
        foreach (AlarmGroup::$availableChannels as $channel) {
            $receiver[$channel] = [];
        }
        $users = [];

        // 获取配置
        $taskConfig = AlarmTaskConfig::where('task_id', $taskId)->select('receiver')->first();
        if (empty($taskConfig) || ! $taskConfig['receiver']) {
            return [$receiver, $users];
        }
        // 解析数据
        $jsonReceiver = json_decode($taskConfig['receiver'], true);
        if (! is_array($jsonReceiver)) {
            return [$receiver, $users];
        }

        $uids = [];

        // 分级告警
        $dispatchMode = empty($jsonReceiver['mode']) ? AlarmTask::RECV_DISPATCH_MODE_LAZY : $jsonReceiver['mode'];
        // 分级告警条件匹配
        $matched = false;
        foreach ($jsonReceiver['dispatch'] as $dispatch) {
            $matchedCond = $this->matchDispatchReceiver($dispatch['conditions'], $history);
            if ($matchedCond) {
                $matched = true;
                $this->getReceiverFields($receiver, $uids, $dispatch['receiver']);
                if ($dispatchMode == AlarmTask::RECV_DISPATCH_MODE_LAZY) {
                    break;
                }
            }
        }
        if (! $matched || ($matched && $dispatchMode == AlarmTask::RECV_DISPATCH_MODE_UNLAZY)) {
            $this->getReceiverFields($receiver, $uids, $jsonReceiver);
        }

        // 合并去重
        foreach ($receiver as $channel => $recv) {
            if (! in_array($channel, AlarmGroup::$availableChannelsRobot)) {
                $receiver[$channel] = array_unique($recv);
            }
        }

        // 查询关联用户信息
        $users = User::whereIn('uid', array_unique($uids))->select('uid', 'username', 'email', 'phone')
            ->get()
            ->keyBy('uid')
            ->toArray();

        return [$receiver, $users];
    }

    /**
     * 发送工作流变更通知.
     * @param mixed $noticeVars
     * @param mixed $scene
     */
    public function sendPipelineNotice($noticeVars, $scene)
    {
        $templates = config('workflow-templates.' . $scene);

        $parallel = new Parallel();
        foreach ($noticeVars as $noticeVar) {
            $parallel->add(function () use ($scene, $templates, $noticeVar) {
                [$receiver, $users] = $this->getReceiver($noticeVar['task']['id'], $noticeVar['history']);

                $parallelInner = new Parallel();
                foreach ($receiver as $channel => $noticer) {
                    $parallelInner->add(function () use ($channel, $noticer, $users, $scene, $templates, $noticeVar) {
                        // 通知人为空，则跳过
                        if (empty($noticer)) {
                            return;
                        }
                        $noticeHandle = 'noticeBy' . ucfirst($channel);
                        // 不支持的通知方式跳过
                        if (! method_exists($this, $noticeHandle)) {
                            return;
                        }
                        $config = [
                            'event' => Noticer::EVENT_WORKFLOW,
                            'scene' => $scene,
                        ];
                        try {
                            $this->{$noticeHandle}($templates[$channel] ?? [], $noticeVar, $noticer, $users, $config);
                        } catch (Throwable $e) {
                            ExceptionHandler::logException($e);
                            return [$channel, sprintf('%s(%s)', $e->getMessage(), $e->getCode())];
                        }
                    });
                }
                return [$noticeVar['workflow']['id'], $parallelInner->wait()];
            });
        }

        $result = $parallel->wait();

        // 将结果转为error输出
        $errors = [];
        foreach ($result as $innerResult) {
            foreach ($innerResult[1] as $error) {
                if (empty($error)) {
                    continue;
                }
                $errors[$innerResult[0]][$error[0]] = $error[1];
            }
        }
        return $errors;
    }

    /**
     * 发送短信通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeBySms($template, $vars, $noticer, $users)
    {
        /**
         * @var Sms
         */
        $sms = make(Sms::class);

        try {
            $tplId = config('noticer.channel.sms.tpl_id');
            $content = mb_substr(AlarmTemplate::formatTemplate($template, $vars), 0, 300);
            $receivers = $this->parseSmsReceiver($noticer, $users);
            $sms->send($tplId, [$content], $receivers);
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送钉钉工作通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByDingworker($template, $vars, $noticer, $users)
    {
        $content = AlarmTemplate::formatTemplate($template, $vars);
        $text = new DingWorkerText($content . "\n" . date('Y-m-d H:i:s'));

        try {
            /**
             * @var DingWorker
             */
            $dingworker = make(DingWorker::class);
            $options = [
                'workcodes' => $this->parseDingworkerReceiver($noticer, $users),
            ];
            $dingworker->send($text, $options);
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送Yach工作通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByYachworker($template, $vars, $noticer, $users)
    {
        $content = AlarmTemplate::formatTemplate($template, $vars);
        $text = new YachWorkerText($content . "\n" . date('Y-m-d H:i:s'));

        try {
            /**
             * @var YachWorker
             */
            $yachworker = make(YachWorker::class);
            $options = [
                'user_type' => 'workcode',
                'userid_list' => $this->parseYachworkerReceiver($noticer, $users),
            ];
            $yachworker->send($text, $options);
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送钉钉机器人通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByDinggroup($template, $vars, $noticer, $users)
    {
        $content = AlarmTemplate::formatTemplate($template, $vars);
        $text = new DingGroupText($content);

        try {
            /**
             * @var DingGroup
             */
            $dinggroup = make(DingGroup::class);
            $options = [];
            $dinggroup->send($text, $noticer, $options);
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送Yach机器人通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByYachgroup($template, $vars, $noticer, $users)
    {
        $content = AlarmTemplate::formatTemplate($template, $vars);
        $text = new YachGroupText($content);

        try {
            /**
             * @var YachGroup
             */
            $yachgroup = make(YachGroup::class);
            $yachgroup->send($text, $noticer);
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送邮件通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByEmail($template, $vars, $noticer, $users)
    {
        $to = $this->parseEmailReceiver($noticer, $users);
        if (empty($to)) {
            return;
        }
        $content = AlarmTemplate::formatTemplate($template, $vars);

        /**
         * @var Email
         */
        $mail = make(Email::class);
        try {
            // 发送纯文本
            $mail->init()
                ->to($to)
                ->subject($template['subject'])
                ->text($content)
                ->send();
        } catch (NoticeException $e) {
            // TODO do something for exception
            throw $e;
        }
    }

    /**
     * 发送电话通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     */
    public function noticeByPhone($template, $vars, $noticer, $users)
    {
        /**
         * @var Phone
         */
        $phone = make(Phone::class);
        $content = mb_substr(AlarmTemplate::formatTemplate($template, $vars), 0, 280);

        foreach ($noticer as $uid) {
            if (! isset($users[$uid]) || empty($users[$uid]['phone'])) {
                continue;
            }
            try {
                $phone->send($content, $users[$uid]['phone']);
            } catch (NoticeException $e) {
                // TODO do something for exception
                throw $e;
            }
        }
    }

    /**
     * 发送Webhook通知.
     * @param mixed $template
     * @param mixed $vars
     * @param mixed $noticer
     * @param mixed $users
     * @param mixed $config
     */
    public function noticeByWebhook($template, $vars, $noticer, $users, $config)
    {
        $guzzleConfig = array_merge(config('noticer.guzzle', []), config('noticer.channel.webhook.guzzle', []));

        /**
         * @var GuzzleHttpClient
         */
        $guzzle = Guzzle::create($guzzleConfig);

        $data = $this->getWebhookNoticeData($vars, $config);

        $hasSent = [];
        foreach ($noticer as $webhook) {
            // 避免重复发送
            $hash = md5($webhook['url']);
            if (isset($hasSent[$hash])) {
                continue;
            }
            $hasSent[$hash] = 1;

            try {
                $resp = $guzzle->post($webhook['url'], ['json' => $data]);
                if ($resp->getStatusCode() != 200) {
                    throw new AppException('response status code is not 200 on webhook notice');
                }
            } catch (Throwable $e) {
                // TODO logger
                // throw $e;
            }
        }
    }

    /**
     * 校验权限.
     * @param $taskId
     * @param $user
     * @return bool
     */
    public function validatePermission($taskId, $user)
    {
        if ($user->isAdmin()) {
            return true;
        }
        if (is_array($taskId)) {
            $userTaskIds = $this->alarmTaskPermission->getTaskIdByUid($user['uid']);
            $notPermission = array_diff($taskId, $userTaskIds);
            if ($notPermission) {
                throw new ForbiddenException(implode(',', $notPermission) . ' 您没有权限操作');
            }
        } else {
            $hasPermission = $this->alarmTaskPermission
                ->where('task_id', $taskId)
                ->where('uid', $user['uid'])
                ->exists();

            if (! $hasPermission) {
                throw new ForbiddenException('您没有权限操作');
            }
        }
        return true;
    }

    /**
     * 获取工作流并判断是否都存在.
     * @param $workflowIds
     * @return Collection
     */
    protected function getWorkflowsAndThrow($workflowIds)
    {
        $workflows = $this->whereIn('id', $workflowIds)
            ->with('task:id,name')
            ->with('history:id,uuid,level,ctn,notice_time,metric,batch')
            ->select('id', 'task_id', 'history_id', 'created_at', 'status')
            ->get();

        // 判断是否workflows都存在
        if (count($workflowIds) != $workflows->count()) {
            $notExists = implode(', ', array_diff($workflowIds, $workflows->pluck('id')->toArray()));
            throw new AppException("workflow [{$notExists}] not exists");
        }

        return $workflows;
    }

    /**
     * 获取receiver中的alarmgroup和channels通知人信息.
     * @param mixed $receiver
     * @param mixed $uids
     * @param mixed $jsonReceiver
     */
    protected function getReceiverFields(&$receiver, &$uids, $jsonReceiver)
    {
        // 数组组装
        if (! empty($jsonReceiver['alarmgroup'])) {
            // 告警通知组
            $groups = AlarmGroup::whereIn('id', $jsonReceiver['alarmgroup'])
                ->select('id', 'receiver')
                ->get();
            foreach ($groups as $group) {
                foreach ($group['receiver']['channels'] as $channel => $recv) {
                    if (! isset($receiver[$channel])) {
                        continue;
                    }
                    if (in_array($channel, AlarmGroup::$availableChannelsUser)) {
                        $uids = array_merge($uids, $recv);
                    }
                    if ($channel == AlarmGroup::CHANNEL_WEBHOOK) {
                        // 允许多个合并
                        $receiver[$channel][] = $recv;
                    } else {
                        $receiver[$channel] = array_merge($receiver[$channel], $recv);
                    }
                }
            }
        }
        if (! empty($jsonReceiver['channels'])) {
            foreach ($jsonReceiver['channels'] as $channel => $recv) {
                if (! isset($receiver[$channel])) {
                    continue;
                }
                if (in_array($channel, AlarmGroup::$availableChannelsUser)) {
                    $uids = array_merge($uids, $recv);
                }
                if ($channel == AlarmGroup::CHANNEL_WEBHOOK) {
                    // 允许多个合并
                    $receiver[$channel][] = $recv;
                } else {
                    $receiver[$channel] = array_merge($receiver[$channel], $recv);
                }
            }
        }
    }

    /**
     * 匹配分级告警条件.
     * @param mixed $conditions
     * @param mixed $history
     */
    protected function matchDispatchReceiver($conditions, $history)
    {
        // 逻辑OR比较，比较原则是只要有条件匹配，立马返回true
        foreach ($conditions as $condition) {
            if ($this->matchDispatchReceiverRule($condition['rule'], $history) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * 匹配分级告警条件.
     * @param mixed $rules
     * @param mixed $history
     */
    protected function matchDispatchReceiverRule($rules, $history)
    {
        // 逻辑AND比较，比较原则是只要有条件不匹配，立马返回false
        foreach ($rules as $rule) {
            $value = Arr::get($history, $rule['field'], null);
            switch ($rule['operator']) {
                case 'eq':
                    if ($value != $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'neq':
                    if ($value == $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'isset':
                    if (! Arr::has($history, $rule['field'])) {
                        return false;
                    }
                    break;
                case 'not-isset':
                    if (Arr::has($history, $rule['field'])) {
                        return false;
                    }
                    break;
                case 'lt':
                    if ($value >= $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'gt':
                    if ($value <= $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'lte':
                    if ($value > $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'gte':
                    if ($value < $rule['threshold']) {
                        return false;
                    }
                    break;
                case 'in':
                    if (! is_array($rule['threshold']) || ! in_array($value, $rule['threshold'])) {
                        return false;
                    }
                    break;
                case 'not-in':
                    if (! is_array($rule['threshold']) || in_array($value, $rule['threshold'])) {
                        return false;
                    }
                    break;
                case 'contain':
                    if (strpos($value, $rule['threshold']) === false) {
                        return false;
                    }
                    break;
                case 'not-contain':
                    if (strpos($value, $rule['threshold']) !== false) {
                        return false;
                    }
                    break;
                default:
                    // do nothing
                    // 其他操作符不支持，但不报错
                    break;
            }
        }
        return true;
    }

    protected function parseSmsReceiver($noticer, $users)
    {
        $recv = [];
        foreach ($noticer as $uid) {
            if (isset($users[$uid]) && ! empty($users[$uid]['phone'])) {
                $recv[] = $users[$uid]['phone'];
            }
        }
        return array_unique($recv);
    }

    protected function parseDingworkerReceiver($noticer, $users)
    {
        $recv = [];
        foreach ($noticer as $uid) {
            if (isset($users[$uid])) {
                $recv[sprintf('%06d', $users[$uid]['uid'])] = 1;
            }
        }
        return implode('|', array_keys($recv));
    }

    protected function parseYachworkerReceiver($noticer, $users)
    {
        $recv = [];
        foreach ($noticer as $uid) {
            if (isset($users[$uid])) {
                $recv[sprintf('%06d', $users[$uid]['uid'])] = 1;
            }
        }
        return implode('|', array_keys($recv));
    }

    protected function parseEmailReceiver($noticer, $users)
    {
        $recv = [];
        foreach ($noticer as $uid) {
            if (isset($users[$uid])) {
                $email = $users[$uid]['email'];
                $recv[$email] = $users[$uid]['username'];
            }
        }
        return $recv;
    }

    /**
     * 获取webhook的发送参数.
     * @param mixed $vars
     * @param mixed $config
     */
    protected function getWebhookNoticeData($vars, $config)
    {
        return [
            'event' => $config['event'],
            'type' => self::$webhookSceneMap[$config['scene']],
            'data' => $vars,
            'extra' => new stdClass(),
        ];
    }

    /**
     * 更改延迟队列.
     * @param mixed $workflows
     * @param null|mixed $fromStatus
     * @param null|mixed $toStatus
     */
    protected function setDelayQueue($workflows, $fromStatus = null, $toStatus = null)
    {
        // 移除已存在的队列
        if ($fromStatus !== null) {
            $workflowIds = $workflows->pluck('id')->toArray();
            // delete from status
            DelayQueueWorkflow::whereIn('workflow_id', $workflowIds)->where('status', $fromStatus)->delete();
        }

        // 增加新队列
        if ($toStatus !== null) {
            $taskIds = $workflows->pluck('task_id')->toArray();
            $taskConfigs = AlarmTaskConfig::whereIn('task_id', $taskIds)->select('workflow', 'task_id')->get();
            $time = time();
            $workflowConfigs = [];
            foreach ($taskConfigs as $taskConfig) {
                $workflowConfigs[$taskConfig['task_id']] = json_decode($taskConfig['workflow'], true);
            }

            $insertQueues = [];
            foreach ($workflows as $workflow) {
                if (empty($workflowConfigs[$workflow['task_id']]) || empty($workflowConfigs[$workflow['task_id']]['reminds'])) {
                    continue;
                }

                foreach ($workflowConfigs[$workflow['task_id']]['reminds'] as $remind) {
                    if ($remind['status'] != $toStatus) {
                        continue;
                    }
                    $insertQueues[] = [
                        'task_id' => $workflow['task_id'],
                        'workflow_id' => $workflow['id'],
                        'history_id' => $workflow['history_id'],
                        'status' => $remind['status'],
                        'interval' => $remind['interval'],
                        'trigger_time' => $time + (int) $remind['interval'] * 60,
                        'created_at' => $time,
                        'updated_at' => $time,
                    ];
                }
            }
            if (! empty($insertQueues)) {
                Db::table('delay_queue_workflow')->insert($insertQueues);
            }
        }
    }
}
