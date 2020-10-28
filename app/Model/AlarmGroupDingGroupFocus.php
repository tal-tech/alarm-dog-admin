<?php

declare(strict_types=1);

namespace App\Model;

class AlarmGroupDingGroupFocus extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_group_dinggroupfocus';

    protected $fillable = ['group_id', 'uid', 'keywords'];
}
