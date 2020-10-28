<?php

declare(strict_types=1);

namespace App\Controller;

use App\Consts\Noticer;
use App\Context\Auth;
use App\Model\AlarmGroup;
use App\Model\AlarmTask;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class AlarmTaskController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTask
     */
    protected $alarmTask;

    /**
     * 任务列表.
     */
    public function list()
    {
        $param = $this->validate([
            'departmentId' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'tagId' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'order' => 'nullable',
        ]);
        $param = array_null2default($param, [
            'departmentId' => null,
            'search' => null,
            'tagId' => null,
            'page' => 1,
            'pageSize' => 20,
            'order' => [],
        ]);

        $user = Context::get(Auth::class)->user();

        $data = $this->alarmTask->list(
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order'],
            $param['departmentId'],
            $param['tagId'],
            $user
        );

        return $this->success($data);
    }

    /**
     * 简单任务列表.
     */
    public function simpleList()
    {
        $param = $this->validate([
            'search' => 'nullable|string',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'department_id' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'search' => null,
            'pageSize' => null,
            'department_id' => null,
        ]);

        $user = Context::get(Auth::class)->user();

        $tasks = $this->alarmTask->simpleList(
            $param['pageSize'],
            $param['search'],
            $param['department_id'],
            $user
        );

        return $this->success(['tasks' => $tasks]);
    }

    /**
     * 简单任务列表(全部).
     */
    public function simpleAll()
    {
        $param = $this->validate([
            'search' => 'nullable|string',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'department_id' => 'nullable|integer',
        ]);
        $param = array_null2default($param, [
            'search' => null,
            'pageSize' => null,
            'department_id' => null,
        ]);

        $tasks = $this->alarmTask->simpleList(
            $param['pageSize'],
            $param['search'],
            $param['department_id']
        );

        return $this->success(['tasks' => $tasks]);
    }

    public function store()
    {
        $param = $this->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'required|integer',
            'task_tags' => 'array',
            'flag_save_db' => 'required|integer|in:0,1',
            'receiver' => 'required|array',
            'permission' => 'array',
            'template' => 'array',
            'workflow' => 'array',
            'filter' => 'array',
            'upgrade' => 'array',
            'compress' => 'array',
            'recovery' => 'array',
        ]);

        $user = Context::get(Auth::class)->user();

        [$task, $nullPhoneUsers] = $this->alarmTask->storeTask($param, $user);

        // 载入关联数据，设置显示属性
        $task->load('department', 'creator');
        $task->setVisible(['id', 'name', 'department_id', 'created_at', 'status', 'created_by', 'department', 'creator']);

        return $this->success([
            'task' => $task,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'name' => 'required|string|max:100',
            'department_id' => 'required|integer',
            'task_tags' => 'array',
            'flag_save_db' => 'required|integer|in:0,1',
            'receiver' => 'required|array',
            'permission' => 'array',
            'template' => 'array',
            'workflow' => 'array',
            'filter' => 'array',
            'upgrade' => 'array',
            'compress' => 'array',
            'recovery' => 'array',
        ]);

        $user = Context::get(Auth::class)->user();

        [$task, $nullPhoneUsers] = $this->alarmTask->updateTask($param['id'], $param, $user);

        // 载入关联数据，设置显示属性
        $task->load('department', 'creator');
        $task->setVisible(['id', 'name', 'department_id', 'created_at', 'status', 'created_by', 'department', 'creator']);

        return $this->success([
            'task' => $task,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $this->alarmTask->deleteTask($taskId, $user);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 停止任务
     */
    public function stop()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $this->alarmTask->stopTask($taskId, $user);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 启动/恢复任务
     */
    public function start()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $this->alarmTask->startTask($taskId, $user);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 停止任务
     */
    public function pause()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'time' => 'required|integer|min:1',
        ]);

        $user = Context::get(Auth::class)->user();

        $this->alarmTask->pauseTask($param['id'], $param['time'], $user);

        return $this->success([
            'id' => (int) $param,
            'time' => (int) $param['time'],
        ]);
    }

    /**
     * 重置token.
     */
    public function resetToken()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $task = $this->alarmTask->resetToken($taskId, $user);

        return $this->success([
            'id' => (int) $task->id,
            'token' => $task->token,
        ]);
    }

    /**
     * 重置secret.
     */
    public function resetSecret()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();

        $task = $this->alarmTask->resetSecret($taskId, $user);

        return $this->success([
            'id' => (int) $task->id,
            'secret' => $task->secret,
        ]);
    }

    /**
     * 上报告警信息.
     */
    public function reportAlarm()
    {
        $param = $this->validate([
            'taskid' => 'required|integer',
            'ctn' => 'required|array',
            'level' => 'nullable|integer|in:' . implode(',', array_keys(AlarmTask::$levels)),
            'notice_time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'level' => null,
            'notice_time' => null,
        ]);

        if (array_keys($param['ctn']) === range(0, count($param['ctn']) - 1)) {
            return $this->failed('ctn must be a JSON Object');
        }

        $res = $this->alarmTask->reportAlarm($param['taskid'], $param);
        $res['taskid'] = (int) $param['taskid'];

        return $this->success($res);
    }

    /**
     * 告警任务详情.
     */
    public function show()
    {
        $taskId = (int) $this->request->input('id');

        $user = Context::get(Auth::class)->user();
        $task = $this->alarmTask->showTask($taskId, $user);
        $apiAuth = $this->alarmTask->genSign($task);

        return $this->success(['task' => $task, 'api_auth' => $apiAuth]);
    }

    /**
     * 验证YachGroup, DingGroup机器人的WebToken和Secret参数并发送一条消息.
     */
    public function validRobotParam()
    {
        $param = $this->validate([
            'webtoken' => 'required|string',
            'secret' => 'required|string',
            'channels' => 'required|string|in:' . implode(',', [AlarmGroup::CHANNEL_DINGGROUP, AlarmGroup::CHANNEL_YACHGROUP]),
        ]);

        $this->alarmTask->validateRobotParam($param);

        return $this->success('Done, WebToken and Secret parameters are acceptable!');
    }

    /**
     * 验证并请求WebHook地址
     */
    public function validWebHookAddress()
    {
        $param = $this->validate([
            'event' => 'required|string|in:' . implode([Noticer::EVENT_PING]),
            'type' => 'required|string|in:' . implode([Noticer::SCENE_PING_PING]),
            'webhook_url' => 'required|string',
            'data' => 'nullable',
        ]);

        $this->alarmTask->validWebHookAddress($param);

        return $this->success('200 OK, WebHook address are acceptable!');
    }

    /**
     * 显示任务阈值
     */
    public function getRateLimit()
    {
        $param = $this->validate([
            'taskid' => 'required|integer',
        ]);

        $rateLimit = $this->alarmTask->getRateLimit($param['taskid']);

        return $this->success(['rate_limit' => $rateLimit]);
    }

    /**
     * 超管修改任务阈值
     */
    public function updateRateLimit()
    {
        $param = $this->validate([
            'taskid' => 'required|integer',
            'rate_limit' => 'required|integer',
        ]);

        $user = Context::get(Auth::class)->user();

        $props = $this->alarmTask->updateRateLimit($param['taskid'], $param['rate_limit'], $user);

        return $this->success(['props' => $props]);
    }

    /**
     * 根据tag获取告警任务简单列表.
     * @return PsrResponseInterface
     */
    public function simpleByTag()
    {
        $params = $this->validate([
            'tag_id' => 'required|integer|min:1',
            'search' => 'nullable|string',
            'pageSize' => 'nullable|integer',
        ]);
        $params = array_null2default($params, [
            'tag_id' => 0,
            'search' => '',
            'pageSize' => 20,
        ]);
        $tasks = $this->alarmTask->simpleByTag($params['tag_id'], $params['search'], $params['pageSize']);
        return $this->success(['tasks' => $tasks]);
    }
}
