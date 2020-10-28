<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskAlarmGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task_alarm_group', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->integer('group_id', false, true)->default(0)->comment('告警组ID');
            $table->tinyInteger('type', false, true)->default(1)->comment('关联类型：1-告警通知人；2-告警升级；3-告警工作流');
            $table->index('task_id', 'idx_taskid');
            $table->index('group_id', 'idx_groupid');
        });
        HelpersForMigration::commentTable('alarm_task_alarm_group', '告警任务与告警组关联表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task_alarm_group');
    }
}
