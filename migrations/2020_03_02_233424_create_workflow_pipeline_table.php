<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWorkflowPipelineTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow_pipeline', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->integer('workflow_id', false, true)->default(0)->comment('工作流ID');
            $table->tinyInteger('status', false, true)->default(0)->comment('状态：0-待处理；1-处理中；2-处理完成；9-关闭');
            $table->string('remark', 2000)->default('')->comment('备注留言');
            $table->integer('created_by', false, true)->default(0)->comment('创建人，0为系统');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->index('task_id', 'idx_taskid');
            $table->index('workflow_id', 'idx_workflowid');
            $table->index('created_by', 'idx_createdby');
            $table->index('created_at', 'idx_createdat');
        });
        HelpersForMigration::commentTable('workflow_pipeline', '工作流pipeline表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_pipeline');
    }
}
