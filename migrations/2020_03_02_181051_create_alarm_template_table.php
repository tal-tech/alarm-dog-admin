<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTemplateTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_template', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('name', 100)->default('')->comment('告警模板名称');
            $table->string('remark', 200)->default('')->comment('备注');
            $table->text('template')->nullable()->comment('告警通知模板');
            $table->integer('created_by', false, true)->default(0)->comment('创建人');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
        });
        HelpersForMigration::commentTable('alarm_template', '告警通知模板表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_template');
    }
}
