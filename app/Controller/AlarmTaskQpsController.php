<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\AlarmTask;
use App\Model\AlarmTaskQps;
use Hyperf\Di\Annotation\Inject;

class AlarmTaskQpsController extends AbstractController
{
    /**
     * @Inject
     * @var AlarmTask
     */
    protected $alarmTask;

    /**
     * @Inject
     * @var AlarmTaskQps
     */
    protected $alarmTaskQps;

    /**
     * 实时QPS.
     */
    public function getDynamicQps()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'taskid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'tagid' => null,
            'taskid' => null,
        ]);

        [$serverQps, $topTenQpsTasks] = $this->alarmTaskQps->getDynamicQps($param['departmentid'], $param['taskid'], $param['tagid']);

        return $this->success([
            'server_qps' => $serverQps,
            'top_ten_req_avg_qps_tasks' => empty($topTenQpsTasks) ? null : $topTenQpsTasks[0],
            'top_ten_req_max_qps_tasks' => empty($topTenQpsTasks) ? null : $topTenQpsTasks[1],
            'top_ten_prod_avg_qps_tasks' => empty($topTenQpsTasks) ? null : $topTenQpsTasks[2],
            'top_ten_prod_max_qps_tasks' => empty($topTenQpsTasks) ? null : $topTenQpsTasks[3],
        ]);
    }

    /**
     * 离线QPS.
     */
    public function getOffLineQps()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'taskid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
            'time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'taskid' => null,
            'tagid' => null,
            'time' => null,
        ]);

        $data = $this->alarmTaskQps->getOffLineQps($param['departmentid'], $param['taskid'], $param['tagid'], $param['time']);

        return $this->success([
            'task_qps' => $data,
        ]);
    }

    /**
     * 条件筛选，累计/今日生产量.
     */
    public function getProdNumber()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'taskid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
            'time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'taskid' => null,
            'tagid' => null,
            'time' => null,
        ]);

        $data = $this->alarmTaskQps->getProdNumber($param['departmentid'], $param['taskid'], $param['tagid'], $param['time']);

        return $this->success([
            'prod_number' => $data,
        ]);
    }

    /**
     * 任务总数.
     */
    public function getTasksNumber()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
            'time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'tagid' => null,
            'time' => null,
        ]);

        $data = $this->alarmTaskQps->getTasksNumber($param['departmentid'], $param['tagid'], $param['time']);

        return $this->success([
            'tasks_total_number' => $data,
        ]);
    }

    /**
     * 今日活跃任务
     */
    public function getActiveTasks()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
            'time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'tagid' => null,
            'time' => null,
        ]);

        $data = $this->alarmTaskQps->getActiveTasks($param['departmentid'], $param['tagid'], $param['time']);

        return $this->success([
            'tasks_active_number' => $data,
        ]);
    }

    /**
     * 统计各状态数量.
     */
    public function getWorkFlowStatus()
    {
        $param = $this->validate([
            'departmentid' => 'nullable|integer|min:1',
            'taskid' => 'nullable|integer|min:1',
            'tagid' => 'nullable|integer|min:1',
            'time' => 'nullable|integer',
        ]);

        $param = array_null2default($param, [
            'departmentid' => null,
            'taskid' => null,
            'tagid' => null,
            'time' => null,
        ]);

        $stats = $this->alarmTaskQps->statsByStatus($param['departmentid'], $param['taskid'], $param['tagid'], $param['time']);

        return $this->success([
            'statistics' => $stats,
        ]);
    }
}
