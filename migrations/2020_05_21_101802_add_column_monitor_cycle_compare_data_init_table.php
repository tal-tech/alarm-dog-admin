<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnMonitorCycleCompareDataInitTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->text('data_init')->nullable()->comment('数据初始化配置')->after('config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->dropColumn('data_init');
        });
    }
}
