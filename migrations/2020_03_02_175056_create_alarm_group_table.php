<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_group', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('name', 100)->default('')->comment('告警组名称');
            $table->string('remark', 200)->default('')->comment('备注');
            $table->integer('created_by', false, true)->default(0)->comment('创建人');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
        });
        HelpersForMigration::commentTable('alarm_group', '告警通知组表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_group');
    }
}
