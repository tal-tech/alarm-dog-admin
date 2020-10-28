<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnPropsAlarmTaskTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alarm_task', function (Blueprint $table) {
            $table->text('props')->nullable()->comment('任务限流等其它配置json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarm_task', function (Blueprint $table) {
            $table->dropColumn('props');
        });
    }
}
