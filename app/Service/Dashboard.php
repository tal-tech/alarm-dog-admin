<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\AlarmTask;
use App\Model\AlarmTaskQps;
use Hyperf\Di\Annotation\Inject;

class Dashboard
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
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getStats($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        //history_count
        $history_count = $this->getHistoryCount($departmentId, $taskId, $tagId, $time);
        //task_count
        $task_count = $this->getTaskCount($departmentId, $taskId, $tagId, $time);
        //active_task_count
        $active_task_count = $this->getActiveTaskCount($departmentId, $taskId, $tagId, $time);
        //workflow_count
        $workflow_count = $this->getWorkflowCount($departmentId, $taskId, $tagId, $time);

        return compact('history_count', 'task_count', 'active_task_count', 'workflow_count');
    }

    /**
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getAvgReqQps($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $top10 = $this->alarmTaskQps->getAvgTop10($departmentId, $taskId, $tagId, $time, 'req_avg_qps');
        $points = $this->alarmTaskQps->getQpsStatByField($departmentId, $taskId, $tagId, $time, 'req_avg_qps');
        return compact('top10', 'points');
    }

    /**
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getMaxReqQps($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $top10 = $this->alarmTaskQps->getMaxTop10($departmentId, $taskId, $tagId, $time, 'req_max_qps');
        $points = $this->alarmTaskQps->getQpsStatByField($departmentId, $taskId, $tagId, $time, 'req_max_qps');
        return compact('top10', 'points');
    }

    /**
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getAvgProdQps($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $top10 = $this->alarmTaskQps->getAvgTop10($departmentId, $taskId, $tagId, $time, 'prod_avg_qps');
        $points = $this->alarmTaskQps->getQpsStatByField($departmentId, $taskId, $tagId, $time, 'prod_avg_qps');
        return compact('top10', 'points');
    }

    /**
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getMaxProdQps($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $top10 = $this->alarmTaskQps->getMaxTop10($departmentId, $taskId, $tagId, $time, 'prod_max_qps');
        $points = $this->alarmTaskQps->getQpsStatByField($departmentId, $taskId, $tagId, $time, 'prod_max_qps');
        return compact('top10', 'points');
    }

    /**
     * 任务告警数统计
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getHistoryCount($departmentId, $taskId, $tagId, $time)
    {
        $time = strtotime(date('Y-m-d 23:59:59', $time));
        $today = $this->alarmTaskQps->getHistoryCount($departmentId, $taskId, $tagId, $time);
        $yesterday = $this->alarmTaskQps->getHistoryCount($departmentId, $taskId, $tagId, strtotime('-1 day', $time));
        $lastweek = $this->alarmTaskQps->getHistoryCount($departmentId, $taskId, $tagId, strtotime('-1 week', $time));
        return compact('today', 'yesterday', 'lastweek');
    }

    /**
     * 任务总数.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getTaskCount($departmentId, $taskId, $tagId, $time)
    {
        $time = strtotime(date('Y-m-d 23:59:59', $time));
        $today = $this->alarmTask
            ->getTaskIdsOrCount($departmentId, $taskId, $tagId, $time, true);
        $yesterday = $this->alarmTask
            ->getTaskIdsOrCount($departmentId, $taskId, $tagId, strtotime('-1 day', $time), true);
        $lastweek = $this->alarmTask
            ->getTaskIdsOrCount($departmentId, $taskId, $tagId, strtotime('-1 week', $time), true);
        return compact('today', 'yesterday', 'lastweek');
    }

    /**
     * 活跃任务数.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getActiveTaskCount($departmentId, $taskId, $tagId, $time)
    {
        $today = $this->alarmTaskQps->getActiveTasks($departmentId, $taskId, $tagId, $time);
        $yesterday = $this->alarmTaskQps->getActiveTasks($departmentId, $taskId, $tagId, strtotime('-1 day', $time));
        $lastweek = $this->alarmTaskQps->getActiveTasks($departmentId, $taskId, $tagId, strtotime('-1 week', $time));
        return compact('today', 'yesterday', 'lastweek');
    }

    /**
     * 工作流数据统计
     * @param $departmentId
     * @param $taskId
     * @param $tagId
     * @param $time
     * @return array
     */
    public function getWorkflowCount($departmentId, $taskId, $tagId, $time)
    {
        $stats = $this->alarmTaskQps->statsByStatus($departmentId, $taskId, $tagId, $time);
        return [
            'pending' => $stats[0] ?? 0, // 待处理
            'claim' => $stats[1] ?? 0, // 认领(处理中)
            'finish' => $stats[2] ?? 0, // 完成(处理完成)
            'close' => $stats[9] ?? 0, // 关闭
        ];
    }
}
