<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmGroupYachgroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_group_yachgroup', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('group_id', false, true)->default(0)->comment('告警组ID');
            $table->string('webhook', 127)->default('')->comment('Yach机器人的WebHook地址');
            $table->string('secret', 127)->default('')->comment('Yach机器人安全签名secret');
            $table->index('group_id', 'idx_groupid');
        });
        HelpersForMigration::commentTable('alarm_group_yachgroup', '告警通知组Yach群关联表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_group_yachgroup');
    }
}
