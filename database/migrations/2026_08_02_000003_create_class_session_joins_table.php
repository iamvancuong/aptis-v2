<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nhật ký vào lớp online.
 *
 * KHÔNG dùng chung bảng `security_flags`: bảng đó dành cho vi phạm (phát hiện
 * DevTools), đổ mọi lượt vào lớp hợp lệ vào đó sẽ làm chìm mất cảnh báo thật.
 *
 * Mục đích: nội quy hiển thị cho học viên nói "mỗi lần vào lớp đều được ghi lại"
 * — phải ghi thật thì lời đó mới đúng. Ngoài ra dùng để phát hiện chia sẻ link:
 * một tài khoản vào lớp từ nhiều địa chỉ mạng khác nhau trong cùng buổi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_session_joins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();   // 45 ký tự đủ cho IPv6
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            // Xem nhật ký theo buổi, và đếm số IP khác nhau của một học viên.
            $table->index(['class_session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_joins');
    }
};
