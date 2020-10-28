<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskQpsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task_qps', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->decimal('req_avg_qps', 6, 2)->default(0)->comment('接口调用QPS');
            $table->integer('req_max_qps', false, true)->default(0)->comment('接口调用QPS最大值');
            $table->decimal('prod_avg_qps', 6, 2)->default(0)->comment('生产QPS');
            $table->integer('prod_max_qps', false, true)->default(0)->comment('生产QPS最大值');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');

            $table->index('task_id', 'idx_taskid');
            $table->index('created_at', 'idx_createdat');
            // 后期优化时再加
            // $table->index('req_max_qps', 'idx_reqmaxqps');
            // $table->index('prod_max_qps', 'idx_prodmaxqps');
        });
        HelpersForMigration::commentTable('alarm_task_qps', '告警任务QPS表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task_qps');
    }
}
