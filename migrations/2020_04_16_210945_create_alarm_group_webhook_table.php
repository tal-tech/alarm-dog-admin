<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmGroupWebhookTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_group_webhook', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('group_id', false, true)->default(0)->comment('告警组ID');
            $table->string('url', 511)->default('')->comment('url地址');
            $table->string('config', 4095)->default('')->comment('配置信息');
            $table->index('group_id', 'idx_groupid');
        });
        HelpersForMigration::commentTable('alarm_group_webhook', '告警通知组webhook关联表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_group_webhook');
    }
}
