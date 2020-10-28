<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnMonitorCycleCompareIsDataInitTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->tinyInteger('is_data_init', false, true)->default(0)->comment('是否已初始化数据')->after('data_init');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitor_cycle_compare', function (Blueprint $table) {
            $table->dropColumn('is_data_init');
        });
    }
}
