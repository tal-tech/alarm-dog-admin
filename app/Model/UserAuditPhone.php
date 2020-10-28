<?php

declare(strict_types=1);

namespace App\Model;

class UserAuditPhone extends Model
{
    public $timestamps = false;

    protected $table = 'user_audit_phone';

    protected $fillable = ['uid', 'old_phone', 'new_phone', 'created_at'];
}
