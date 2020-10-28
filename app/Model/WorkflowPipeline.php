<?php

declare(strict_types=1);

namespace App\Model;

use stdClass;

class WorkflowPipeline extends Model
{
    public $timestamps = false;

    protected $table = 'workflow_pipeline';

    protected $fillable = ['task_id', 'workflow_id', 'status', 'remark', 'props', 'created_by', 'created_at'];

    public function creator()
    {
        return $this->hasOne(User::class, 'uid', 'created_by')->select('uid', 'username', 'email', 'department');
    }

    /**
     * 工作流信息.
     *
     * @param int $workflowId
     * @return array
     */
    public function pipelines($workflowId)
    {
        $pipelines = $this->where('workflow_id', $workflowId)
            ->select('status', 'remark', 'props', 'created_by', 'created_at')
            ->get()
            ->toArray();

        // 收集uid，统一查询
        $uids = [];
        foreach ($pipelines as $pipeline) {
            if ($pipeline['created_by']) {
                $uids[] = $pipeline['created_by'];
            }
            if ($pipeline['status'] == Workflow::STATUS_ASSIGN) {
                $props = json_decode($pipeline['props'], true);
                if ($props && ! empty($props['assignto'])) {
                    $uids = array_merge($uids, $props['assignto']);
                }
            }
        }
        $uids = array_unique($uids);

        $users = User::whereIn('uid', $uids)
            ->select('uid', 'username', 'email', 'department')
            ->get()
            ->keyBy('uid')
            ->toArray();

        // 格式化
        foreach ($pipelines as &$pipeline) {
            if ($pipeline['created_by'] && isset($users[$pipeline['created_by']])) {
                $pipeline['creator'] = $users[$pipeline['created_by']];
            } else {
                $pipeline['creator'] = null;
            }

            if ($pipeline['status'] == Workflow::STATUS_ASSIGN) {
                // 指派
                $jsonProps = json_decode($pipeline['props'], true);
                $props = ['assignto' => []];
                if ($jsonProps && ! empty($jsonProps['assignto'])) {
                    foreach ($jsonProps['assignto'] as $assignTo) {
                        if (isset($users[$assignTo])) {
                            $props['assignto'][] = $users[$assignTo];
                        }
                    }
                }
                $pipeline['props'] = $props;
            } elseif ($pipeline['status'] == Workflow::STATUS_REMIND) {
                // 提醒
                $jsonProps = json_decode($pipeline['props'], true);
                $props = ['remind' => ['interval' => 0, 'status' => -1]];
                if ($jsonProps && ! empty($jsonProps['remind'])) {
                    if (isset($jsonProps['remind']['interval'])) {
                        $props['remind']['interval'] = (int) $jsonProps['remind']['interval'];
                    }
                    if (isset($jsonProps['remind']['status'])) {
                        $props['remind']['status'] = (int) $jsonProps['remind']['status'];
                    }
                }
                $pipeline['props'] = $props;
            } else {
                $pipeline['props'] = new stdClass();
            }
        }
        unset($pipeline);

        return $pipelines;
    }
}
