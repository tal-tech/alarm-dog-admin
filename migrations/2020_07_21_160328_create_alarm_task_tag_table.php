<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTaskTagTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_task_tag', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('tag_id', false, true)->default(0)->comment('标签ID');
            $table->integer('task_id', false, true)->default(0)->comment('告警任务ID');

            $table->index('tag_id', 'idx_tagid');
            $table->index('task_id', 'idx_taskid');
        });
        HelpersForMigration::commentTable('alarm_task_tag', '标签关联告警任务表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_task_tag');
    }
}
