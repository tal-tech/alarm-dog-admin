<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMonitorProtocolDetectTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_protocol_detect', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('关联告警任务ID');
            $table->string('name', 100)->default('')->comment('监控任务名称');
            $table->string('pinyin', 500)->default('')->comment('拼音');
            $table->string('remark', 500)->default('')->comment('备注');
            $table->string('token', 100)->default('')->comment('后面开放接口鉴权用');
            $table->tinyInteger('protocol', false, true)->default(1)->comment('协议，枚举值参考配置文件');
            $table->integer('monitor_frequency', false, true)->default(0)->comment('监控频率，单位秒，可枚举');
            $table->text('config')->nullable()->comment('监控配置');
            $table->text('alarm_condition')->nullable()->comment('告警条件');
            $table->tinyInteger('status', false, true)->default(1)->comment('监控任务状态，见任务配置');
            $table->integer('created_by', false, true)->default(0)->comment('创建人ID');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->index('task_id', 'idx_taskid');
            $table->index('updated_at', 'idx_updatedat');
            $table->index('created_by', 'idx_createdby');
        });
        HelpersForMigration::commentTable('monitor_protocol_detect', '心跳探活监控任务表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_protocol_detect');
    }
}
