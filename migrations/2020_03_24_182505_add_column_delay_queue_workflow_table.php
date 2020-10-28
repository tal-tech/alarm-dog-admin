<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnDelayQueueWorkflowTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delay_queue_workflow', function (Blueprint $table) {
            $table->integer('history_id', false, true)->default(0)->comment('告警记录ID')->after('workflow_id');
            $table->integer('interval', false, true)->default(0)->comment('提醒时间间隔')->after('status');
            $table->index('history_id', 'idx_historyid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delay_queue_workflow', function (Blueprint $table) {
            $table->dropIndex('idx_historyid');
            $table->dropColumn('history_id');
            $table->dropColumn('interval');
        });
    }
}
