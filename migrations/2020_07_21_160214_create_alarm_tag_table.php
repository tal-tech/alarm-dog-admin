<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_tag', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('name', 100)->default('')->comment('标签名称');
            $table->string('pinyin', 500)->default('')->comment('拼音');
            $table->string('remark', 200)->default('')->comment('备注');
            $table->integer('created_by', false, true)->default(0)->comment('创建人');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->index('updated_at', 'idx_updatedat');
            $table->index('created_by', 'idx_createdby');
        });
        HelpersForMigration::commentTable('alarm_tag', '标签管理表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_tag');
    }
}
