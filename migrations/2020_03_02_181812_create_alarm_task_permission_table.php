<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskPermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task_permission', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->tinyInteger('type', false, true)->default(0)->comment('权限类型：1-读写；2-只读');
            $table->integer('uid', false, true)->default(0)->comment('用户ID');

            $table->index('task_id', 'idx_taskid');
            $table->index('uid', 'idx_uid');
        });
        HelpersForMigration::commentTable('alarm_task_permission', '告警任务用户权限表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task_permission');
    }
}
