<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Throwable;

class AlarmTaskAlarmGroup extends Model
{
    // 告警通知人
    public const TYPE_RECEIVER = 1;

    // 告警升级
    public const TYPE_UPGRADE = 2;

    // 告警工作流
    public const TYPE_WORKFLOW = 3;

    public $timestamps = false;

    protected $table = 'alarm_task_alarm_group';

    protected $fillable = ['task_id', 'group_id', 'type'];

    /**
     * @param int $taskId
     * @param array $permission
     * @param bool $beforeDelete
     * @param mixed $scenes
     */
    public function saveGroups($taskId, $scenes, $beforeDelete = false)
    {
        $types = [
            'receiver' => static::TYPE_RECEIVER,
            'upgrade' => static::TYPE_UPGRADE,
            'workflow' => static::TYPE_WORKFLOW,
        ];
        $inserts = [];
        foreach (['receiver', 'upgrade', 'workflow'] as $scene) {
            if (! isset($scenes[$scene]) || ! isset($scenes[$scene]['alarmgroup'])) {
                continue;
            }
            foreach ($scenes[$scene]['alarmgroup'] as $groupId) {
                $inserts[] = [
                    'task_id' => $taskId,
                    'group_id' => $groupId,
                    'type' => $types[$scene],
                ];
            }
        }

        // 使用事务是为了避免删除与新插入不完整
        Db::beginTransaction();
        try {
            if ($beforeDelete) {
                Db::table($this->table)->where('task_id', $taskId)->delete();
            }
            if (! empty($inserts)) {
                Db::table($this->table)->insert($inserts);
            }
            Db::commit();
        } catch (Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }
}
