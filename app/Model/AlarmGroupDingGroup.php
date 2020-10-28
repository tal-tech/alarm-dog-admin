<?php

declare(strict_types=1);

namespace App\Model;

class AlarmGroupDingGroup extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_group_dinggroup';

    protected $fillable = ['group_id', 'webhook', 'secret'];
}
