<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelayQueueWorkflowTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delay_queue_workflow', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->integer('workflow_id', false, true)->default(0)->comment('工作流ID');
            $table->tinyInteger('status', false, true)->default(0)->comment('工作流状态');
            $table->integer('trigger_time', false, true)->default(0)->comment('延迟队列触发时间');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
            $table->index('task_id', 'idx_taskid');
            $table->index('workflow_id', 'idx_workflowid');
            $table->index('trigger_time', 'idx_triggertime');
            $table->index('updated_at', 'idx_updatedat');
        });
        HelpersForMigration::commentTable('delay_queue_workflow', '工作流延迟队列表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delay_queue_workflow');
    }
}
