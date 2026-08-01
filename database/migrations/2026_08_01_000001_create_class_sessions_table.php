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
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
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
