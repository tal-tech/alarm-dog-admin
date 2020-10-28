<?php

declare(strict_types=1);

namespace App\Model;

class DelayQueueWorkflow extends Model
{
    public $timestamps = false;

    protected $table = 'delay_queue_workflow';

    protected $fillable = ['task_id', 'workflow_id', 'history_id', 'status', 'interval', 'trigger_time', 'created_at', 'updated_at'];
}
