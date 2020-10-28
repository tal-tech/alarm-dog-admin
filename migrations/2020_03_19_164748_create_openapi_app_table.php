<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateOpenapiAppTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('openapi_app', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('appid', false, true)->default(0)->comment('应用ID');
            $table->string('token', 40)->default('')->comment('鉴权token');
            $table->string('name', 100)->default('')->comment('应用名称');
            $table->string('remark', 200)->default('')->comment('备注');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');
            $table->unique('appid', 'uniq_appid');
        });
        HelpersForMigration::commentTable('openapi_app', 'OPENAPI应用表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openapi_app');
    }
}
