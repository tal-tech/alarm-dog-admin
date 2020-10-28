<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAlarmTemplatePermissionTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alarm_template_permission', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('template_id', false, true)->default(0)->comment('告警模板ID');
            $table->integer('uid', false, true)->default(0)->comment('用户ID');

            $table->index('template_id', 'idx_templateid');
            $table->index('uid', 'idx_uid');
        });
        HelpersForMigration::commentTable('alarm_template_permission', '告警模板用户权限表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alarm_template_permission');
    }
}
