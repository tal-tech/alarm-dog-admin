<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmGroupPermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_group_permission', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('group_id', false, true)->default(0)->comment('告警通知组ID');
            $table->integer('uid', false, true)->default(0)->comment('用户ID');

            $table->index('group_id', 'idx_groupid');
            $table->index('uid', 'idx_uid');
        });
        HelpersForMigration::commentTable('alarm_group_permission', '告警通知组用户权限表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_group_permission');
    }
}
