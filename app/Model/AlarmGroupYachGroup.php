<?php

declare(strict_types=1);

namespace App\Model;

class AlarmGroupYachGroup extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_group_yachgroup';

    protected $fillable = ['group_id', 'webhook', 'secret'];
}
