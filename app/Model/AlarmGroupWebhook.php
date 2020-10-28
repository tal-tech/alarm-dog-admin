<?php

declare(strict_types=1);

namespace App\Model;

class AlarmGroupWebhook extends Model
{
    public $timestamps = false;

    protected $table = 'alarm_group_webhook';

    protected $fillable = ['group_id', 'url', 'config'];

    protected $casts = [
        'config' => 'array',
    ];
}
