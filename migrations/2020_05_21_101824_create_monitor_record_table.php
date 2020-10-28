<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMonitorRecordTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_record_1', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->tinyInteger('monitor_type', false, true)->default(0)->comment('监控类型：1-通用；2-同环比；3-突增突降');
            $table->integer('taskid', false, true)->default(0)->comment('监控任务名称');
            $table->string('alarm_rule_id', 500)->default('')->comment('命中告警的规则ID，未命中为空，多个以,分隔');
            $table->text('fields')->nullable()->comment('字段值');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');

            $table->index('taskid', 'idx_taskid');
            $table->index('created_at', 'idx_createdat');
            $table->index('alarm_rule_id', 'idx_alarmruleid');
        });
        HelpersForMigration::commentTable('monitor_record_1', '监控记录表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_record_1');
    }
}
