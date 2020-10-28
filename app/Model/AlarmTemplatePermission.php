<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;
use Throwable;

class AlarmTemplatePermission extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_template_permission';

    protected $fillable = ['template_id', 'uid'];

    /**
     * @param int $templateId
     * @param array $permission
     * @param bool $beforeDelete
     */
    public function savePermission($templateId, $permission, $beforeDelete = false)
    {
        $permission = array_unique($permission);

        $inserts = [];
        foreach ($permission as $uid) {
            $inserts[] = [
                'template_id' => $templateId,
                'uid' => $uid,
            ];
        }

        // 使用事务是为了避免删除与新插入不完整
        Db::beginTransaction();
        try {
            if ($beforeDelete) {
                Db::table($this->table)->where('template_id', $templateId)->delete();
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
