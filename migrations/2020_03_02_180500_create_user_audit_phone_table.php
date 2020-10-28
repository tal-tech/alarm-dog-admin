<?php

declare(strict_types=1);

use App\Support\HelpersForMigration;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateUserAuditPhoneTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_audit_phone', function (Blueprint $table) {
            $table->integer('id', true, true)->comment('自增ID');
            $table->integer('uid', false, true)->default(0)->comment('用户ID');
            $table->string('old_phone', 50)->default('')->comment('老手机号');
            $table->string('new_phone', 50)->default('')->comment('新手机号');
            $table->integer('created_at', false, true)->default(0)->comment('创建时间');

            $table->index('uid', 'idx_uid');
        });
        HelpersForMigration::commentTable('user_audit_phone', '用户手机号修改审计表');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_audit_phone');
    }
}
