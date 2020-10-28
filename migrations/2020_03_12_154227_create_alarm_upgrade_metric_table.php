<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmUpgradeMetricTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_upgrade_metric', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->string('metric', 40)->default('')->comment('告警收敛指标值，未收敛为空');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->unique(['task_id', 'metric'], 'uniq_taskid_metric');
            $table->index('task_id', 'idx_taskid');
            $table->index('metric', 'idx_metric');
        });
        HelpersForMigration::commentTable('alarm_upgrade_metric', '告警升级关联的metric信息表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_upgrade_metric');
    }
}
