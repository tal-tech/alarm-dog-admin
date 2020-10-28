<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMonitorDatasourceTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitor_datasource', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->tinyInteger('type', false, true)->default(0)->comment('数据源类型，枚举见代码配置');
            $table->string('name', 100)->default('')->comment('名称');
            $table->string('pinyin', 500)->default('')->comment('拼音');
            $table->string('remark', 500)->default('')->comment('备注');
            $table->text('config')->nullable()->comment('连接配置');
            $table->text('fields')->nullable()->comment('字段');
            $table->string('timestamp_field', 100)->nullable()->comment('时间戳字段');
            $table->tinyInteger('timestamp_unit', false, true)->default(1)->comment('时间戳类型：1-秒；2-毫秒；3-纳秒');
            $table->integer('created_by', false, true)->default(0)->comment('创建人ID');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->index('updated_at', 'idx_updatedat');
            $table->index('created_by', 'idx_createdby');
        });
        HelpersForMigration::commentTable('monitor_datasource', '监控数据源配置表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_datasource');
    }
}
