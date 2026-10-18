<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `users.vocab_extra_daily`: số lượt tra từ cô cấp THÊM mỗi ngày cho riêng học
 * viên này, cộng vào mức chung (Admin → Cài đặt). Mặc định 0 = như mọi người.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('vocab_extra_daily')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('vocab_extra_daily');
        });
    }
};
