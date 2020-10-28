<?php

declare(strict_types=1);

namespace App\Controller\Monitor;

use App\Context\Auth;
use App\Controller\AbstractController;
use App\Model\MonitorProtocolDetect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;

class ProtocolDetectController extends AbstractController
{
    /**
     * @Inject
     * @var MonitorProtocolDetect
     */
    protected $task;

    /**
     * 列表.
     */
    public function list()
    {
        $param = $this->validate([
            'department_id' => 'nullable|integer',
            'task_id' => 'nullable|integer',
            'status' => 'nullable|integer|in:' . implode(',', array_keys(MonitorProtocolDetect::$statuses)),
            'search' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
            'order' => 'nullable',
        ]);
        $param = array_null2default($param, [
            'department_id' => null,
            'task_id' => null,
            'status' => null,
            'search' => null,
            'page' => 1,
            'pageSize' => 20,
            'order' => [],
        ]);

        $data = $this->task->list(
            $param['department_id'],
            $param['task_id'],
            $param['status'],
            $param['page'],
            $param['pageSize'],
            $param['search'],
            $param['order']
        );

        return $this->success($data);
    }

    /**
     * 详情.
     */
    public function show()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $task = $this->task->showTask($param['id']);

        return $this->success([
            'task' => $task,
        ]);
    }

    /**
     * 删除.
     */
    public function delete()
    {
        $param = $this->validate([
            'id' => 'required|integer',
        ]);

        $user = Context::get(Auth::class)->user();

        $this->task->deleteTask($param['id'], $user);

        return $this->success([
            'id' => (int) $param['id'],
        ]);
    }

    /**
     * 简单列表.
     */
    public function simpleList()
    {
        $search = $this->request->input('search');
        $pageSize = $this->request->input('pageSize', null);

        $tasks = $this->task->simpleList($search, $pageSize);

        return $this->success([
            'tasks' => $tasks,
        ]);
    }

    /**
     * 创建.
     */
    public function store()
    {
        $param = $this->validate([
            'task_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'protocol' => 'required|integer|in:' . implode(',', array_keys(MonitorProtocolDetect::$protocols)),
            'monitor_frequency' => 'required|integer|in:' . implode(',', MonitorProtocolDetect::$monitorFrequencies),
            'config' => 'required|array',
            'alarm_condition' => 'required|array',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $task = $this->task->storeTask($param, $user);

        return $this->success([
            'task' => $task,
        ]);
    }

    /**
     * 更新.
     */
    public function update()
    {
        $param = $this->validate([
            'id' => 'required|integer',
            'task_id' => 'required|integer',
            'name' => 'required|string|max:100',
            'remark' => 'nullable|string|max:200',
            'protocol' => 'required|integer|in:' . implode(',', array_keys(MonitorProtocolDetect::$protocols)),
            'monitor_frequency' => 'required|integer|in:' . implode(',', MonitorProtocolDetect::$monitorFrequencies),
            'config' => 'required|array',
            'alarm_condition' => 'required|array',
        ]);

        $param = array_null2default($param, [
            'remark' => '',
        ]);

        $user = Context::get(Auth::class)->user();

        $task = $this->task->updateTask($param['id'], $param, $user);

        return $this->success([
            'task' => $task,
        ]);
    }

    /**
     * 停止任务
     */
    public function stop()
    {
        $taskId = (int) $this->request->input('id');

        $this->task->stopTask($taskId);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 启动/恢复任务
     */
    public function start()
    {
        $taskId = (int) $this->request->input('id');

        $this->task->startTask($taskId);

        return $this->success(['id' => $taskId]);
    }

    /**
     * 重置token.
     */
    public function resetToken()
    {
        $taskId = (int) $this->request->input('id');

        $task = $this->task->resetToken($taskId);

        return $this->success([
            'id' => (int) $task->id,
            'token' => $task->token,
        ]);
    }

    /**
     * 验证连接是否可用.
     */
    public function validConnect()
    {
        $param = $this->validate([
            'protocol' => 'required|integer|in:' . implode(',', array_keys(MonitorProtocolDetect::$protocols)),
            'config' => 'required|array',
        ]);

        $resp = $this->task->validConnect($param);

        return $this->success($resp);
    }
}
