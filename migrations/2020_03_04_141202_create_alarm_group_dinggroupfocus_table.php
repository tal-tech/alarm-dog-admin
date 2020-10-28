<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmGroupDinggroupfocusTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_group_dinggroupfocus', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('group_id', false, true)->default(0)->comment('告警组ID');
            $table->integer('uid', false, true)->default(0)->comment('关联用户ID');
            $table->text('keywords')->nullable()->comment('关注关键词');
            $table->index('group_id', 'idx_groupid');
            $table->index('uid', 'idx_uid');
        });
        HelpersForMigration::commentTable('alarm_group_dinggroupfocus', '告警通知组钉钉群关键词关注关联表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_group_dinggroupfocus');
    }
}
