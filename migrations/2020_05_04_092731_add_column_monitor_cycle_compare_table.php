<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnMonitorCycleCompareTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->integer('started_at', false, true)->default(0)->comment('任务启动时间')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });
    }
}
