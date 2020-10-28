<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Throwable;

class AlarmGroupPermission extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_group_permission';

    protected $fillable = ['group_id', 'uid'];

    /**
     * @param int $groupId
     * @param array $permission
     * @param bool $beforeDelete
     */
    public function savePermission($groupId, $permission, $beforeDelete = false)
    {
        $permission = array_unique($permission);

        $inserts = [];
        foreach ($permission as $uid) {
            $inserts[] = [
                'group_id' => $groupId,
                'uid' => $uid,
            ];
        }

        // 使用事务是为了避免删除与新插入不完整
        Db::beginTransaction();
        try {
            if ($beforeDelete) {
                Db::table($this->table)->where('group_id', $groupId)->delete();
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
     * 告警通知组列表.
     *
     * @param int $uid
     * @return array
     */
    public function getGroupsByUid($uid)
    {
        return $this->where('uid', $uid)
            ->pluck('group_id')
            ->toArray();
    }
}
