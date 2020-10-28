<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnAlarmGroupDinggroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alarm_group_dinggroup', function (Blueprint $table) {
            $table->integer('webhook_crc32', false, true)->default(0)->comment('webhook的crc32，注意无符号处理')->after('secret');
            $table->string('webhook', 255)->change();
            $table->string('secret', 255)->change();
            $table->index('webhook_crc32', 'idx_webhookcrc32');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarm_group_dinggroup', function (Blueprint $table) {
            $table->dropColumn('webhook_crc32');
            $table->string('webhook', 50)->change();
            $table->string('secret', 50)->change();
        });
    }
}
