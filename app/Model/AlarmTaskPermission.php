<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Throwable;

class AlarmTaskPermission extends Model
{
    // 读写权限
    public const TYPE_RW = 1;

    // 只读权限
    public const TYPE_RO = 2;

    public $timestamps = false;

    protected $table = 'alarm_task_permission';

    protected $fillable = ['task_id', 'type', 'uid'];

    /**
     * @param int $taskId
     * @param array $permission
     * @param bool $beforeDelete
     */
    public function savePermission($taskId, $permission, $beforeDelete = false)
    {
        $inserts = [];
        foreach (['rw', 'ro'] as $type) {
            foreach ($permission[$type] as $uid) {
                $inserts[] = [
                    'task_id' => $taskId,
                    'type' => $type == 'rw' ? static::TYPE_RW : static::TYPE_RO,
                    'uid' => $uid,
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

    /**
     * 根据uid 获取 1-读写 2-只读 的task id.
     * @param $uid
     * @param int $type
     * @return mixed
     */
    public function getPermissionTaskIDByUid($uid, $type = 1)
    {
        return $this->select('task_id')->where([
            'uid' => $uid,
            'type' => $type,
        ])->get()->toArray();
    }

    /**
     * 告警通知组列表.
     *
     * @return array
     */
    public function getTaskIdByUid(int $uid)
    {
        return $this->select('task_id')
            ->where('uid', $uid)
            ->pluck('task_id')
            ->toArray();
    }
}
