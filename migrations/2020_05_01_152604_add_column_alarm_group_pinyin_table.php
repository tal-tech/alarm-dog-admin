<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnAlarmGroupPinyinTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('alarm_group', function (Blueprint $table) {
            $table->string('pinyin', 500)->default('')->comment('拼音')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alarm_group', function (Blueprint $table) {
            $table->dropColumn('pinyin');
        });
    }
}
