<?php

declare(strict_types=1);

namespace App\Controller\OpenApi;

use App\Model\AlarmTask;
use App\Model\User;
use Hyperf\Di\Annotation\Inject;

class AlarmTaskController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTask
     */
    protected $alarmTask;

    /**
     * @Inject
     * @var User
     */
    protected $user;

    public function store()
    {
        $param = $this->validate([
            'name' => 'required|string|max:100',
            'department_id' => 'required|integer',
            'task_tags' => 'array',
            'task_tags.*' => 'integer|distinct',
            'flag_save_db' => 'required|integer|in:0,1',
            'receiver' => 'required|array',
            'permission' => 'array',
            'template' => 'array',
            'workflow' => 'array',
            'filter' => 'array',
            'upgrade' => 'array',
            'compress' => 'array',
            'recovery' => 'array',
            'created_by' => 'required|integer',
        ]);

        $user = $this->validateUser($param['created_by']);

        [$task, $nullPhoneUsers] = $this->alarmTask->storeTask($param, $user);

        // 载入关联数据，设置显示属性
        $task->load('department', 'creator');
        $task->setVisible([
            'id', 'name', 'department_id', 'created_at', 'status', 'created_by', 'department', 'creator', 'token',
            'secret', 'status',
        ]);

        return $this->success([
            'task' => $task,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
            'name' => 'required|string|max:100',
            'department_id' => 'required|integer',
            'task_tags' => 'array',
            'task_tags.*' => 'integer|distinct',
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

        // 校验签名
        AlarmTask::fastCheckOpenApi($param);

        [$task, $nullPhoneUsers] = $this->alarmTask->updateTask($param['id'], $param, true);

        // 载入关联数据，设置显示属性
        $task->load('department', 'creator');
        $task->setVisible([
            'id', 'name', 'department_id', 'created_at', 'status', 'created_by', 'department', 'creator', 'token',
            'secret', 'status',
        ]);

        return $this->success([
            'task' => $task,
            'nullPhoneUsers' => $nullPhoneUsers,
        ]);
    }

    /**
     * 更新部分字段.
     */
    public function updateFields()
    {
        $paramBasic = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
            'filter_fields' => 'required|array',
        ]);
        // filter_fields校验
        $possiableRules = [
            'name' => 'required|string|max:100',
            'department_id' => 'required|integer',
            'flag_save_db' => 'required|integer|in:0,1',
            'receiver' => 'required|array',
            'permission' => 'array',
            'template' => 'array',
            'workflow' => 'array',
            'filter' => 'array',
            'upgrade' => 'array',
            'compress' => 'array',
            'recovery' => 'array',
            'task_tags' => 'array',
            'task_tags.*' => 'integer|distinct',
        ];
        $rules = [];
        foreach ($paramBasic['filter_fields'] as $field) {
            $key = explode('.', $field)[0];
            if (! isset($possiableRules[$key])) {
                return $this->failed("invalid filter_fileds [{$field}]");
            }
            // 利用map自动去重
            $rules[$key] = $possiableRules[$key];
        }
        $param = $this->validate($rules);

        // 校验签名
        AlarmTask::fastCheckOpenApi($paramBasic);

        [$task, $nullPhoneUsers] = $this->alarmTask->updateFilterFields($paramBasic['id'], $param, true, $paramBasic['filter_fields']);

        // 载入关联数据，设置显示属性
        $task->load('department', 'creator');
        $task->setVisible([
            'id', 'name', 'department_id', 'created_at', 'status', 'created_by', 'department', 'creator', 'token',
            'secret', 'status',
        ]);

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
        // 校验签名
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
        ]);
        AlarmTask::fastCheckOpenApi($param);

        $taskId = (int) $this->request->input('id');

        $this->alarmTask->deleteTask($taskId, true);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 停止任务
     */
    public function stop()
    {
        // 校验签名
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
        ]);
        AlarmTask::fastCheckOpenApi($param);

        $taskId = (int) $this->request->input('id');

        $this->alarmTask->stopTask($taskId);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 启动/恢复任务
     */
    public function start()
    {
        // 校验签名
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
        ]);
        AlarmTask::fastCheckOpenApi($param);

        $taskId = (int) $this->request->input('id');

        $this->alarmTask->startTask($taskId);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 停止任务
     */
    public function pause()
    {
        // 校验签名
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
            'time' => 'required|integer|min:1',
        ]);
        AlarmTask::fastCheckOpenApi($param);

        $this->alarmTask->pauseTask($param['id'], $param['time']);

        return $this->success(['id' => (int) $param['id'], 'time' => (int) $param['time']]);
    }

    /**
     * 告警任务详情.
     */
    public function show()
    {
        // 校验签名
        $param = $this->validate([
            'id' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
        ]);
        AlarmTask::fastCheckOpenApi($param);

        $taskId = (int) $this->request->input('id');

        $task = $this->alarmTask->showTask($taskId);

        return $this->success(['task' => $task]);
    }
}
