<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnAlarmGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alarm_group', function (Blueprint $table) {
            $table->text('receiver')->nullable()->comment('自定义通知人配置冗余存储')->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarm_group', function (Blueprint $table) {
            $table->dropColumn('receiver');
        });
    }
}
