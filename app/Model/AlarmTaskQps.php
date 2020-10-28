<?php

declare(strict_types=1);

namespace App\Model;

use App\Command\AlarmTask\SyncQps;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;

class AlarmTaskQps extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_task_qps';

    protected $fillable = ['task_id', 'req_avg_qps', 'req_max_qps', 'prod_avg_qps', 'prod_max_qps', 'created_at'];

    /**
     * @Inject
     * @var AlarmTask
     */
    protected $alarmTask;

    /**
     * @Inject
     * @var SyncQps
     */
    protected $syncQps;

    /**
     * @Inject
     * @var Workflow
     */
    protected $workflow;

    /**
     * @Inject
     * @var AlarmHistory
     */
    protected $alarmHistory;

    /**
     * @Inject
     * @var AlarmTaskTag
     */
    protected $alarmTaskTag;

    /**
     * @Inject
     * @var AlarmTag
     */
    protected $alarmTag;

    /**
     * @Inject
     * @var Department
     */
    protected $department;

    /**
     * @Inject
     * @var Redis
     */
    protected $redis;

    // protected $minDate = '2020-08-28 10:41:00';

    /**
     * 按条件获取服务端生产量（不精确统计）.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return int|mixed
     */
    public function getProdNumber($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $builder = $this->alarmHistory->newQuery();

        if ($time) {
            $startTime = strtotime(date('Y-m-d', $time));
            $endTime = $startTime + 86399;
            $builder->whereBetween('created_at', [$startTime, $endTime]);
        }
        if ($taskId) {
            $this->alarmTask->getById($taskId, true);
            $builder->where('task_id', $taskId);
        } elseif ($departmentId) {
            $this->department->getByIdAndThrow($departmentId);
            $tasksId = $this->alarmTask->where('department_id', $departmentId)->pluck('id')->toArray();
            $builder->whereIn('task_id', $tasksId);
        } elseif ($tagId) {
            $this->alarmTag->getByIdAndThrow($tagId);
            $taskIds = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        }

        // 日累计
        if ($time) {
            return $builder->count();
        }

        // 总累计
        return $builder->max('id');
    }

    /**
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return int
     */
    public function getHistoryCount($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));
        $taskIds = $this->alarmTask->getTaskIdsOrCount($departmentId, $taskId, $tagId, $time);
        $prod_avg_qps = $this->newQuery()
            ->whereBetween('created_at', [$startTime, $endTime])
            ->whereIn('task_id', $taskIds)
            ->sum('prod_avg_qps') * 60;
        return intval($prod_avg_qps);
    }

    /**
     * 条件查询活跃任务
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return int
     */
    public function getActiveTasks($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        if (! $time) {
            $time = time();
        }

        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));

        $query = $this->select(Db::raw('DISTINCT `task_id`'))
            ->whereBetween('created_at', [$startTime, $endTime]);
        //告警任务ID不为空
        if ($taskId) {
            $query = $query->where('task_id', $taskId);
        }
        $taskIds = $query->pluck('task_id')->toArray();
        if ($taskId) {
            return count($taskIds);
        }
        if ($departmentId) {
            $this->department->getByIdAndThrow($departmentId);
            return $this->alarmTask->where('department_id', $departmentId)
                ->whereIn('id', $taskIds)
                ->count();
        }
        if ($tagId) {
            $this->alarmTag->getByIdAndThrow($tagId);
            return $this->alarmTaskTag->where('tag_id', $tagId)
                ->whereIn('task_id', $taskIds)
                ->count();
        }
        return count($taskIds);
    }

    /**
     * 统计各状态数量.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function statsByStatus($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        if (! $time) {
            $time = time();
        }

        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));

        $builder = $this->workflow->select('status', Db::raw('COUNT(*) AS `count`'))
            ->whereBetween('created_at', [$startTime, $endTime]);

        if ($taskId) {
            $builder->where('task_id', $taskId);
        } elseif ($departmentId) {
            // 查询出所有taskId，然后where in
            $taskIds = AlarmTask::where('department_id', $departmentId)->pluck('id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        } elseif ($tagId) {
            $tasksIdBytagId = AlarmTaskTag::where('tag_id', $tagId)->pluck('task_id')->toArray();
            $builder->whereIn('task_id', $tasksIdBytagId);
        }

        $statsData = $builder->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        // 补充0
        foreach (Workflow::$availableStatuses as $availableStatus => $statusTitle) {
            if (! isset($statsData[$availableStatus])) {
                $statsData[$availableStatus] = 0;
            }
        }
        return $statsData;
    }

    /**
     * 获取统计数据.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @param string $field
     * @return array
     */
    public function getQpsStatByField($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0, $field = 'avg_req_qps')
    {
        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));
        $closeTime = min(time(), $endTime);
        $query = $this->newQuery();
        if ($departmentId || $taskId || $tagId) {
            $taskIds = $this->alarmTask->getTaskIdsOrCount($departmentId, $taskId, $tagId, $time);
            $query->whereIn('task_id', $taskIds);
        }
        $query->whereBetween('created_at', [$startTime, $endTime]);
        $query->select(
            Db::raw("from_unixtime(created_at,'%Y-%m-%d %H:%i:00') as created_at"),
            Db::raw("sum({$field}) as value")
        );
        $pluck = $query->groupBy('created_at')->pluck('value', 'created_at');
        $list = [];
        while ($startTime < $closeTime) {
            $date = date('Y-m-d H:i:s', $startTime);
            $value = $pluck[$date] ?? 0;
            $list[] = [
                // 'date' => $date,
                'created_at' => $startTime,
                'value' => round($value, 2),
            ];
            $startTime += 60;
        }
        return $list;
    }

    public function getAvgTop10($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0, $field = 'avg_req_qps')
    {
        return $this->getTop10($departmentId, $taskId, $tagId, $time, $field, 'avg');
    }

    public function getMaxTop10($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0, $field = 'avg_req_qps')
    {
        return $this->getTop10($departmentId, $taskId, $tagId, $time, $field, 'max');
    }

    /**
     * TODO: 废弃
     * 按条件获取获取任务数.
     * @param int $departmentId
     * @param int $tagId
     * @param int $time
     * @return int
     */
    public function getTasksNumber($departmentId = 0, $tagId = 0, $time = 0)
    {
        $tasks = $this->alarmTask->newQuery();
        if ($time) {
            $startTime = strtotime(date('Y-m-d 00:00:00', $time));
            $endTime = strtotime(date('Y-m-d 23:59:59', $time));
            $tasks = $this->whereBetween('created_at', [$startTime, $endTime]);
        }

        if ($departmentId) {
            $tasks->where('department_id', $departmentId);
        }

        if ($tagId) {
            return $this->alarmTaskTag->where('tag_id', $tagId)
                ->count();
        }

        return $tasks->count();
    }

    /**
     * TopTen排序.
     * @param $data
     * @return array
     */
    public function topTenSort($data)
    {
        $reqAvgQpsTmp = array_column($data, 'req_avg_qps', 'task_id');
        $reqMaxQpsTmp = array_column($data, 'req_max_qps', 'task_id');
        $prodAvgQpsTmp = array_column($data, 'prod_avg_qps', 'task_id');
        $prodMaxQpsTmp = array_column($data, 'prod_max_qps', 'task_id');

        arsort($reqAvgQpsTmp, SORT_DESC);
        arsort($reqMaxQpsTmp, SORT_DESC);
        arsort($prodAvgQpsTmp, SORT_DESC);
        arsort($prodMaxQpsTmp, SORT_DESC);

        return [$reqAvgQpsTmp, $reqMaxQpsTmp, $prodAvgQpsTmp, $prodMaxQpsTmp];
    }

    /**
     * 格式化QPS数据.
     * @param $data
     * @return array
     */
    public function formatQpsData($data)
    {
        $result = [
            'req_avg_qps' => 0.00,
            'req_max_qps' => 0,
            'prod_avg_qps' => 0,
            'prod_max_qps' => 0.00,
            'created_at' => time(),
        ];

        $flag = true;
        foreach ($data as $dataKey => $dataValue) {
            $result['req_avg_qps'] += $dataValue['req_avg_qps'];
            $result['req_max_qps'] = max($result['req_max_qps'], $dataValue['req_max_qps']);
            $result['prod_avg_qps'] += $dataValue['prod_avg_qps'];
            $result['prod_max_qps'] = max($result['prod_max_qps'], $dataValue['prod_max_qps']);
            if ($flag) {
                $result['created_at'] = $dataValue['created_at'];
                $flag = false;
            }
        }

        $taskNum = count($data) ? count($data) : 1;

        $result['req_avg_qps'] = $result['req_avg_qps'] / $taskNum;
        $result['prod_avg_qps'] = $result['prod_avg_qps'] / $taskNum;

        return $result;
    }

    /**
     * 获取动态QPS数据.
     * @param $departmentId
     * @param $taskId
     * @param $tagId
     * @return array
     */
    public function getDynamicQps($departmentId, $taskId, $tagId)
    {
        $topTenTasks = [];
        $time = time();
        $second = (int) date('s', $time);
        $result = [
            'req_avg_qps' => 0.00,
            'req_max_qps' => 0,
            'prod_avg_qps' => 0,
            'prod_max_qps' => 0.00,
            'created_at' => $time,
        ];
        $data = $this->syncQps->statisticsQps($time, false, $second + 1);

        if (empty($data)) {
            return [$result, $topTenTasks];
        }

        if ($taskId) {
            $this->alarmTask->getById($taskId);
            foreach ($data as $dataKey => $dataValue) {
                if ($dataValue['task_id'] == $taskId) {
                    return [$data[$dataKey], $topTenTasks];
                }
            }

            return [$this->formatQpsData($data), $topTenTasks];
        }

        if ($departmentId) {
            $this->department->getByIdAndThrow($departmentId);
            $taskIds = $this->alarmTask->where('department_id', $departmentId)->pluck('id')->toArray();

            foreach ($data as $dataKey => $dataValue) {
                if (array_search($dataValue['task_id'], $taskIds) === false) {
                    unset($data[$dataKey]);
                }
            }
            $topTenTasks = $this->topTenSort($data);

            return [$this->formatQpsData($data), $topTenTasks];
        }

        if ($tagId) {
            $this->alarmTag->getByIdAndThrow($tagId);
            $taskIds = $this->alarmTaskTag->where('tag_id', $tagId)->pluck('task_id')->toArray();
            foreach ($data as $dataKey => $dataValue) {
                if (array_search($dataValue['task_id'], $taskIds) === false) {
                    unset($data[$dataKey]);
                }
            }
            $topTenTasks = $this->topTenSort($data);

            return [$this->formatQpsData($data), $topTenTasks];
        }

        $topTenTasks = $this->topTenSort($data);

        return [$this->formatQpsData($data), $topTenTasks];
    }

    /**
     * 获取离线QPS数据.
     * @param int $departmentId
     * @param int $taskId
     * @param int $tagId
     * @param int $time
     * @return array
     */
    public function getOffLineQps($departmentId = 0, $taskId = 0, $tagId = 0, $time = 0)
    {
        if (! $time) {
            $time = time();
        }

        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));

        $builder = $this->whereBetween('created_at', [$startTime, $endTime])
            ->select(
                Db::raw('SUM(`prod_avg_qps`) / COUNT(`prod_avg_qps`) AS prod_avg_qps'),
                Db::raw('SUM(`req_avg_qps`) / COUNT(`req_avg_qps`) AS req_avg_qps'),
                Db::raw('MAX(`prod_max_qps`) AS prod_max_qps'),
                Db::raw('MAX(`req_max_qps`) AS req_max_qps'),
                'created_at'
            )->groupBy('created_at');

        if ($departmentId) {
            $taskIds = $this->alarmTask->where('department_id', $departmentId)->pluck('id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        }

        if ($taskId) {
            $this->alarmTask->getById($taskId);
            $builder->where('task_id', $taskId);
        }

        if ($tagId) {
            $this->alarmTag->getByIdAndThrow($tagId);
            $taskIds = $this->alarmTaskTag->where('tag_id', $tagId)->pluck('task_id')->toArray();
            $builder->whereIn('task_id', $taskIds);
        }

        return $builder->get();
    }

    protected function getTop10(
        $departmentId = 0,
        $taskId = 0,
        $tagId = 0,
        $time = 0,
        $field = 'avg_req_qps',
        $type = 'avg'
    ) {
        $startTime = strtotime(date('Y-m-d 00:00:00', $time));
        $endTime = strtotime(date('Y-m-d 23:59:59', $time));
        $query = $this->newQuery();
        //taskId 不为空
        if (! $taskId && ($departmentId || $tagId)) {
            $taskIds = $this->alarmTask->getTaskIdsOrCount($departmentId, 0, $tagId, $time);
            $query->whereIn('task_id', $taskIds);
        }
        $query->whereBetween('created_at', [$startTime, $endTime]);
        $query->select('task_id', Db::raw("{$type}({$field}) as value"));
        $pluck = $query->groupBy('task_id')->orderBy('value', 'DESC')
            ->limit(10)->pluck('value', 'task_id')
            ->toArray();
        //获取告警任务名称
        $task_names = $this->alarmTask->getTaskNames(array_keys($pluck));
        $tasks = [];
        foreach ($pluck as $task_id => $value) {
            if (! $value) {
                continue;
            }
            $tasks[] = [
                'task_id' => $task_id,
                'name' => $task_names[$task_id] ?? "告警任务ID: {$task_id} 未找到",
                'value' => round($value, 2),
            ];
        }
        return $tasks;
    }
}
