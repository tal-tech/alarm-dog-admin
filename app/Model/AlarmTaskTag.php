<?php

declare(strict_types=1);

namespace App\Model;

use Hyperf\DbConnection\Db;

class AlarmTaskTag extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_task_tag';

    protected $fillable = ['tag_id', 'task_id'];

    /**
     * 标签数组关联到告警任务
     * @param array $tags
     * @param mixed $taskId
     */
    public function follow($tags, $taskId)
    {
        $data = [];
        foreach ($tags as $tagId) {
            $data[] = [
                'tag_id' => $tagId,
                'task_id' => $taskId,
            ];
        }

        if (! empty($tags)) {
            Db::transaction(function () use ($data) {
                if (! empty($data)) {
                    $this->insert($data);
                }
            });
        }
    }

    /**
     * 找出当前告警任务所关联的标签.
     * @param mixed $taskId
     */
    public function showFollowTag($taskId)
    {
        $tags = $this->where('task_id', $taskId)->pluck('tag_id')->toArray();
        $task_tags = AlarmTag::whereIn('id', $tags)->select('id', 'name', 'remark', 'created_by')->with('creator')->get();

        if (! empty($task_tags)) {
            return $task_tags;
        }

        return [];
    }

    /**
     * 编辑告警任务时，更新关联的标签.
     * @param mixed $tags
     * @param mixed $taskId
     */
    public function updateFollowTag($tags, $taskId)
    {
        $data = [];
        foreach ($tags as $tagId) {
            $data[] = [
                'tag_id' => $tagId,
                'task_id' => $taskId,
            ];
        }

        if (empty($tags)) {
            Db::transaction(function () use ($data, $taskId) {
                $this->where('task_id', $taskId)->delete();
            });
        } else {
            Db::transaction(function () use ($data, $taskId) {
                $this->where('task_id', $taskId)->delete();
                $this->insert($data);
            });
        }
    }
}
