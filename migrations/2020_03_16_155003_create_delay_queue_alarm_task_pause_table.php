<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelayQueueAlarmTaskPauseTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delay_queue_alarm_task_pause', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->integer('interval', false, true)->default(0)->comment('延迟时间');
            $table->integer('trigger_time', false, true)->default(0)->comment('延迟队列触发时间');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
            $table->unique('task_id', 'uniq_taskid');
            $table->index('trigger_time', 'idx_triggertime');
            $table->index('updated_at', 'idx_updatedat');
        });
        HelpersForMigration::commentTable('delay_queue_alarm_task_pause', '延迟队列告警任务停止表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delay_queue_alarm_task_pause');
    }
}
