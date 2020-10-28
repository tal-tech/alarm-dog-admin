<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateWorkflowTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workflow', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->string('metric', 40)->default('')->comment('收敛指标');
            $table->integer('history_id', false, true)->default(0)->comment('告警历史信息ID');
            $table->tinyInteger('status', false, true)->default(0)->comment('状态：0-待处理；1-处理中；2-处理完成；9-关闭');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
            $table->index('task_id', 'idx_taskid');
            $table->index('created_at', 'idx_createdat');
            $table->index('updated_at', 'idx_updatedat');
        });
        HelpersForMigration::commentTable('workflow', '工作流表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow');
    }
}
