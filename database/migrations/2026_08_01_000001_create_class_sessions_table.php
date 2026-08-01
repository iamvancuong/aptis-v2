<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buổi học online (Pha 0): admin tạo buổi + dán link Google Meet thủ công.
 * Chưa gắn học viên vào buổi — mọi tài khoản còn hạn đều vào được buổi đang mở.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            // Link Meet do admin dán. Không bao giờ render ra HTML cho học viên.
            $table->string('meet_link', 500);
            // Cả hai để trống được (giảm thao tác cho giảng viên):
            // starts_at null = mở ngay · ends_at null = không tự đóng.
            // Khi cả hai null thì `is_active` là công tắc bật/tắt duy nhất.
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Danh sách học viên lọc theo đúng 2 cột này (buổi đang bật, chưa kết thúc).
            $table->index(['is_active', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
