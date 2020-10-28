<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateBusinessUnitTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_unit', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('name', 100)->default('')->comment('事业部名称');
            $table->string('pinyin', 500)->default('')->comment('拼音');
            $table->string('remark', 200)->default('')->comment('备注');
            $table->integer('created_by', false, true)->default(0)->comment('创建人');
            $table->integer('updated_by', false, true)->default(0)->comment('最后更新人');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->index('name', 'idx_name');
        });
        HelpersForMigration::commentTable('business_unit', '事业部表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_unit');
    }
}
