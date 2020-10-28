<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Swoole\Coroutine;

class MonitorRecord extends Model
{
    /**
     * 监控类型.
     */
    public const TYPE_UNIVERSAL = 1;

    public const TYPE_CYCLE_COMPARE = 2;

    public const TYPE_UPRUSH_DOWNRUSH = 3;

    /**
     * 表名前缀
     */
    public const TABLE_PREFIX = 'monitor_record_';

    public $timestamps = false;

    protected $table = 'monitor_record_';

    protected $fillable = [
        'monitor_type', 'taskid', 'alarm_rule_id', 'fields', 'created_at',
    ];

    protected $casts = [
        'fields' => 'array',
    ];

    /**
     * 新实例化一个model.
     *
     * @param int $datasourceId
     * @return MonitorRecord
     */
    public static function newModel($datasourceId)
    {
        $model = make(MonitorRecord::class);
        $model->setTable(self::TABLE_PREFIX . $datasourceId);
        return $model;
    }

    /**
     * 保存一批记录.
     * @param mixed $dsId
     * @param mixed $taskId
     * @param mixed $type
     * @param mixed $list
     */
    public static function saveRecords($dsId, $taskId, $type, $list)
    {
        if (empty($list)) {
            return;
        }
        $table = self::TABLE_PREFIX . $dsId;

        $startTime = PHP_INT_MAX;
        $endTime = 0;
        $records = [];
        foreach ($list as $item) {
            $records[] = [
                'monitor_type' => $type,
                'taskid' => $taskId,
                'alarm_rule_id' => '',
                'fields' => json_encode($item['fields']),
                'created_at' => $item['timestamp'],
            ];
            $startTime = min($startTime, $item['timestamp']);
            $endTime = max($endTime, $item['timestamp']);
        }
        // 先删除数据，后新增
        Db::table($table)->where('taskid', $taskId)
            ->where('created_at', '>=', $startTime)
            ->where('created_at', '<=', $endTime)
            ->where('monitor_type', $type)
            ->delete();
        Db::table($table)->insert($records);
    }

    /**
     * 查询记录.
     * @param mixed $dsId
     * @param mixed $taskId
     * @param mixed $type
     * @param null|mixed $startTime
     * @param null|mixed $endTime
     */
    public static function queryRecords($dsId, $taskId, $type, $startTime = null, $endTime = null)
    {
        $model = static::newCommonQueryModel($dsId, $taskId, $type, $startTime, $endTime);

        return $model->get();
    }

    /**
     * 安全删除记录.
     * @param mixed $dsId
     * @param mixed $taskId
     * @param mixed $type
     * @param null|mixed $startTime
     * @param null|mixed $endTime
     */
    public static function secureDelete($dsId, $taskId, $type, $startTime = null, $endTime = null)
    {
        $delSize = (int) config('monitor.record.batch_delete_size', 50000);
        $sleepTime = (float) config('monitor.record.delete_sleep_time', 0.1);
        while (true) {
            $model = static::newCommonQueryModel($dsId, $taskId, $type, $startTime, $endTime);
            $count = $model->limit($delSize)->delete();
            if ($count < $delSize) {
                break;
            }
            // 休眠100ms
            Coroutine::sleep($sleepTime);
        }
    }

    /**
     * 共同的条件模型.
     * @param mixed $dsId
     * @param mixed $taskId
     * @param mixed $type
     * @param null|mixed $startTime
     * @param null|mixed $endTime
     */
    protected static function newCommonQueryModel($dsId, $taskId, $type, $startTime = null, $endTime = null)
    {
        $model = static::newModel($dsId);
        $model->where('taskid', $taskId);

        if ($startTime) {
            $model->where('created_at', '>=', $startTime);
        }
        if ($endTime) {
            $model->where('created_at', '<=', $endTime);
        }

        $model->where('type', $type);

        return $model;
    }
}
