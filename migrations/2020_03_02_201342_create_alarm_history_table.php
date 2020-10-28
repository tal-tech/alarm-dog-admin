<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmHistoryTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_history', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');
            $table->string('uuid', 40)->default('')->comment('告警信息唯一ID');
            $table->integer('batch', false, true)->default(0)->comment('收敛批次ID，crc32取无符号整数，若无收敛则为0或获取失败则为0');
            $table->string('metric', 40)->default('')->comment('收敛指标');
            $table->integer('notice_time', false, true)->default(0)->comment('告警通知时间');
            $table->tinyInteger('level', false, true)->default(0)->comment('告警级别：0-通知；1-警告；2-错误；3-紧急');
            $table->text('ctn')->nullable()->comment('告警内容，json格式存储');
            $table->tinyInteger('type', false, true)->default(1)->comment('告警类型：1-正常告警；2-恢复告警；3-忽略告警');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->unique('uuid', 'uniq_uuid');
            $table->index('task_id', 'idx_taskid');
            $table->index('batch', 'idx_batch');
            $table->index('metric', 'idx_metric');
            $table->index('created_at', 'idx_createdat');
        });
        HelpersForMigration::commentTable('alarm_history', '告警历史信息表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_history');
    }
}
