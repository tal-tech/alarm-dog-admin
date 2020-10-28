<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('name', 100)->default('')->comment('告警任务名称');
            $table->string('token', 40)->default('')->comment('告警上报接口用token');
            $table->string('secret', 40)->default('')->comment('其他接口用secret');
            $table->integer('department_id', false, true)->default(0)->comment('部门ID');
            $table->tinyInteger('flag_save_db', false, true)->default(1)->comment('是否入库存储：1-是；0-否');
            $table->tinyInteger('enable_workflow', false, true)->default(0)->comment('是否开启工作流：1-是；0-否');
            $table->tinyInteger('enable_filter', false, true)->default(0)->comment('是否开启告警过滤：1-是；0-否');
            $table->tinyInteger('enable_compress', false, true)->default(0)->comment('是否开启告警收敛压缩：1-是；0-否');
            $table->tinyInteger('enable_upgrade', false, true)->default(0)->comment('是否开启告警升级：1-是；0-否');
            $table->tinyInteger('enable_recovery', false, true)->default(0)->comment('是否开启告警自动恢复：1-是；0-否');
            $table->tinyInteger('status', false, true)->default(1)->comment('告警任务状态：0-已停止；1-运行中；2-已暂停');
            $table->integer('created_by', false, true)->default(0)->comment('创建人');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->index('department_id', 'idx_departmentid');
        });
        HelpersForMigration::commentTable('alarm_task', '告警通知模板表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task');
    }
}
