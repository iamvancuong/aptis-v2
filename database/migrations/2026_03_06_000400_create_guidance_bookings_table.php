<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đặt lịch buổi hướng dẫn thứ 7. Mỗi user giữ 1 booking hiện hành (đổi được);
 * P7 sẽ gom theo session_date để sinh mã phòng Zoom riêng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('session_date');        // ngày thứ 7 đã chọn
            $table->string('zoom_link')->nullable();
            $table->timestamps();

            $table->unique('user_id'); // 1 booking hiện hành / user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_bookings');
    }
};
