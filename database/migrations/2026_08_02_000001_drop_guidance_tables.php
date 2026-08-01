<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dọn 2 bảng mồ côi của tính năng "buổi hướng dẫn" cũ (đặt lịch + phòng họp).
 *
 * Tính năng đã bị gỡ khỏi code từ lâu, nhưng migration tạo bảng cũng bị xoá
 * cùng lúc — nên không có gì DROP chúng: mọi DB đã chạy trước đó vẫn giữ 2 bảng
 * này vĩnh viễn, kèm các cột lưu link phòng họp. Migration này dọn nốt.
 *
 * ⚠️ Đây là thao tác XOÁ DỮ LIỆU. Trên DB test cả 2 bảng đều 0 dòng; hãy kiểm
 * tra production trước khi chạy:
 *   php artisan tinker --execute="foreach(['guidance_bookings','guidance_sessions'] as \$t) echo \$t.'='.DB::table(\$t)->count().PHP_EOL;"
 *
 * `down()` chỉ dựng lại CẤU TRÚC rỗng, KHÔNG khôi phục được dữ liệu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('guidance_bookings');
        Schema::dropIfExists('guidance_sessions');
    }

    public function down(): void
    {
        Schema::create('guidance_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('session_date');
            $table->string('meeting_id')->nullable();
            $table->string('join_url')->nullable();
            $table->string('start_url')->nullable();
            $table->string('passcode')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('guidance_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('session_date');
            $table->string('join_url')->nullable();
            $table->timestamps();
        });
    }
};
