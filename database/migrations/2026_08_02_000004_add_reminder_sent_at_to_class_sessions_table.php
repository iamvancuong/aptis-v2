<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đánh dấu buổi học đã gửi email nhắc giờ.
 *
 * Cron chạy mỗi phút, không có cột này thì mỗi phút lại gửi lại cho toàn bộ
 * học viên — spam hàng trăm email và có thể bị Gmail chặn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
