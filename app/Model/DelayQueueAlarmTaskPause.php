<?php

declare(strict_types=1);

namespace App\Model;

class DelayQueueAlarmTaskPause extends Model
{
    public $timestamps = false;

    protected $table = 'delay_queue_alarm_task_pause';

    protected $fillable = ['task_id', 'interval', 'trigger_time', 'created_at', 'updated_at'];

    /**
     * 设置一个队列.
     *
     * @param int $taskId
     * @param int $interval 时间，单位：分钟
     */
    public function setQueue($taskId, $interval)
    {
        $now = time();
        $delayQueue = $this->where('task_id', $taskId)->first();
        if (empty($delayQueue)) {
            // 创建
            $data = [
                'task_id' => $taskId,
                'interval' => $interval,
                'trigger_time' => $now + $interval * 60,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $delayQueue = self::create($data);
        } else {
            // 更新
            $delayQueue->interval = $interval;
            $delayQueue->trigger_time = $now + $interval * 60;
            $delayQueue->updated_at = $now;
            $delayQueue->save();
        }
    }

    /**
     * 分页查询暂停任务
     */
    public function getPauseRecoverys(int $maxId, int $time, int $pageSize = 20): array
    {
        $ret = $this->select('id', 'task_id', 'trigger_time')
            ->where('id', '>', $maxId)
            ->where('trigger_time', '<', $time)
            ->limit($pageSize)
            ->get();

        return ! $ret ? [] : $ret->toArray();
    }

    /**
     * 删除处理过的数据.
     *
     * @param $ids
     * @return mixed
     */
    public function deleteByIds(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        return $this->whereIn('id', $ids)->delete();
    }
}
