<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task_config', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->text('workflow')->nullable()->comment('工作流相关配置，以json存储');
            $table->text('compress')->nullable()->comment('告警压缩相关配置，以json存储');
            $table->text('filter')->nullable()->comment('告警过滤相关配置，以json存储');
            $table->text('recovery')->nullable()->comment('告警自动恢复相关配置，以json存储');
            $table->text('upgrade')->nullable()->comment('告警升级相关配置，以json存储');
            $table->text('receiver')->nullable()->comment('告警接收人相关配置，以json存储');
            $table->tinyInteger('alarm_template_id', false, true)->default(0)->comment('告警模板ID，未选择模板时为0');
            $table->text('alarm_template')->nullable()->comment('通知模板相关配置，以json存储');

            $table->unique('task_id', 'uniq_taskid');
        });
        HelpersForMigration::commentTable('alarm_task_config', '告警任务配置表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task_config');
    }
}
