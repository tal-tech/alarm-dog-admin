<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('uid', false, true)->default(0)->comment('用户ID');
            $table->string('account', 50)->default('')->comment('帐号');
            $table->string('username', 50)->default('')->comment('用户姓名');
            $table->string('pinyin', 500)->default('')->comment('拼音');
            $table->string('user', 100)->default('')->comment('邮箱前缀');
            $table->string('email', 100)->default('')->comment('邮箱');
            $table->string('phone', 20)->default('')->comment('手机号');
            $table->string('department', 500)->default('')->comment('部门');
            $table->string('password', 100)->default('')->comment('微信ID，告警使用');
            $table->tinyInteger('role', false, true)->default(0)->comment('角色：9-超管；0-普通用户');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');
            $table->integer('updated_at', false, true)->default(0)->comment('更新时间');

            $table->unique('uid', 'uniq_uid');
            $table->unique('account', 'uniq_account');
        });
        HelpersForMigration::commentTable('user', '用户表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
}
