<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\AppException;
use App\Model\AlarmHistory;
use App\Model\AlarmTask;
use App\Model\Workflow;
use Hyperf\Di\Annotation\Inject;

class AlarmHistoryAll
{
    /**
     * @Inject
     * @var Workflow
     */
    protected $workflow;

    /**
     * 任务历史数据
     * 1. N时间前的数据，存在clickhouse中；
     * 2. 相对近期数据，存在mysql中；.
     */
    public function showHistory(int $historyId): array
    {
        try {
            // 分不同库，查询历史数据
            $historys = $this->getOrderAlarmHistorys([$historyId]);
            if (! isset($historys[$historyId]['task_id'])) {
                return [];
            }

            // 任务和部门
            $historys = $this->getTasksDepartments($historys, [$historys[$historyId]['task_id']]);

            return $historys[$historyId] ?? [];
        } catch (AppException $e) {
            throw new AppException($e->getMessage(), $e->getContext(), $e->getPrevious(), $e->getCode());
        }
    }

    /**
     * 历史任务，指定查询哪个库，兼容clickhouse/mysql.
     */
    public function getAlarmHistorys(array $chHistoryIds = [], array $mysqlHistoryIds = []): array
    {
        $chHistorys = $mysqlHistorys = [];

        // 查询clickhouse历史任务数据
        if (! empty($chHistoryIds)) {
            $chHistorys = make(AlarmHistory::class)->getChAlarmHistorys($chHistoryIds);
        }

        // 查询mysql历史任务数据
        if (! empty($mysqlHistoryIds)) {
            $mysqlHistorys = make(AlarmHistory::class)->getAlarmHistorys($mysqlHistoryIds);
        }

        return $chHistorys + $mysqlHistorys;
    }

    /**
     * 历史任务，按顺度查询库，mysql->clickhouse.
     */
    public function getOrderAlarmHistorys(array $historyIds): array
    {
        $chHistorys = $mysqlHistorys = $chIds = [];
        $count = count($historyIds);

        // 先查询mysql
        $mysqlHistorys = make(AlarmHistory::class)->getAlarmHistorys($historyIds);

        // 验证是否还有没查到的数据
        if ($count == count($mysqlHistorys)) {
            return $mysqlHistorys;
        }

        // 得到在mysql中没有查到的数据
        foreach ($historyIds as $historyId) {
            if (isset($mysqlHistorys[$historyId])) {
                continue;
            }
            $chIds[] = $historyId;
        }

        // 再查询clickhouse
        if (! empty($chIds)) {
            $chHistorys = make(AlarmHistory::class)->getChAlarmHistorys($chIds);
        }

        return $chHistorys + $mysqlHistorys;
    }

    /**
     * 查询任务/部门数据.
     *
     * @param array $workflows
     */
    public function getTasksDepartments(array $historys, array $taskIds): array
    {
        $tasks = make(AlarmTask::class)->getDepartmentByTaskIds($taskIds);
        if (empty($tasks)) {
            return $historys;
        }

        // 格式化id为键的数组
        $tasks = array_column($tasks, null, 'id');

        foreach ($historys as $k => $history) {
            $taskId = $history['task_id'];
            if (! isset($tasks[$taskId])) {
                continue;
            }
            $history['task'] = $tasks[$taskId];

            $historys[$k] = $history;
        }

        return $historys;
    }
}
