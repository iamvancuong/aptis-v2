<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mỗi buổi hướng dẫn (một thứ 7) = một phòng Zoom riêng. Tạo trước buổi học,
 * gửi join_url cho học viên đã đặt lịch và start_url cho admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('session_date')->unique();
            $table->string('zoom_meeting_id')->nullable();
            $table->string('join_url', 1000)->nullable();  // link học viên
            $table->text('start_url')->nullable();          // link host (admin) — dài
            $table->string('passcode')->nullable();
            $table->timestamp('sent_at')->nullable();       // đã gửi email lần cuối
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_sessions');
    }
};
