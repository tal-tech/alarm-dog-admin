<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddColumnWorkflowPipelineTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workflow_pipeline', function (Blueprint $table) {
            $table->text('props')->nullable()->comment('扩展属性信息，json格式存储')->after('remark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workflow_pipeline', function (Blueprint $table) {
            $table->dropColumn('props');
        });
    }
}
