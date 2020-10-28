<?php

declare(strict_types=1);

namespace App\Model;

use App\Exception\AppException;
use App\Service\Pinyin;
use Hyperf\Di\Annotation\Inject;

class MonitorUprushDownrush extends Model
{
    /**
     * 任务状态
     */
    public const STATUS_STARTING = 1;

    public const STATUS_STARTED = 2;

    public const STATUS_STOPPING = 3;

    public const STATUS_STOPPED = 4;

    public const STATUS_EDITED = 6;

    public $timestamps = false;

    protected $table = 'monitor_uprush_downrush';

    protected $fillable = [
        'task_id', 'name', 'pinyin', 'remark', 'token', 'datasource_id', 'agg_cycle', 'config',
        'alarm_condition', 'status', 'created_by', 'created_at', 'updated_at',
    ];

    protected $casts = [
        'config' => 'array',
        'alarm_condition' => 'array',
    ];

    /**
     * @Inject
     * @var Pinyin
     */
    protected $pinyin;

    /**
     * 是否存在该名称的任务
     *
     * @param string $name
     * @param int $excludeId
     * @return int
     */
    public function hasByName($name, $excludeId = 0)
    {
        if ($excludeId) {
            return $this->where('name', $name)->where('id', '<>', $excludeId)->count();
        }
        return $this->where('name', $name)->count();
    }

    /**
     * 判断是否存在，不存在则报错.
     *
     * @param int $taskId
     * @return self
     */
    public function getByIdAndThrow($taskId)
    {
        $task = $this->where('id', $taskId)->first();
        if (empty($task)) {
            throw new AppException("task [{$taskId}] not found", [
                'task_id' => $taskId,
            ]);
        }

        return $task;
    }
}
