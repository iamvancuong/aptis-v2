<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đếm lượt chấm Nói bằng AI, tách hẳn khỏi `writing_ai_usages`.
 *
 * Không gộp chung một bảng với Writing vì hai bên có mốc reset riêng
 * (`users.ai_reset_version` vs `users.speaking_ai_reset_version`) — admin bấm
 * reset Writing không được vô tình cấp lại lượt Nói và ngược lại.
 *
 * Unique gồm cả `reset_version` ngay từ đầu: bảng Writing ban đầu thiếu vế này
 * nên sau khi admin reset, `firstOrCreate` đụng đúng dòng cũ và lượt không hồi
 * lại — đã phải vá bằng một migration riêng (`update_writing_ai_usages_unique_constraint`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speaking_ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('speaking_part'); // 1..4
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedInteger('reset_version')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'speaking_part', 'reset_version']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speaking_ai_usages');
    }
};
