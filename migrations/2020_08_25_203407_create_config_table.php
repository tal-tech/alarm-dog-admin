<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('config', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->string('key', 100)->default('')->comment('配置KEY');
            $table->string('remark', 100)->default('')->comment('备注KEY');
            $table->text('value')->nullable()->comment('配置值');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->unique('key', 'uniq_key');
        });
        HelpersForMigration::commentTable('config', '系统配置表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config');
    }
}
