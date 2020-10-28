<?php

declare(strict_types=1);

namespace App\Model;

class AlarmTaskConfig extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_task_config';

    protected $fillable = ['task_id', 'workflow', 'compress', 'filter', 'recovery', 'upgrade', 'receiver', 'alarm_template_id', 'alarm_template'];

    public function insertTaskConfig($params)
    {
        return $this->insert($params);
    }
}
