<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bộ đếm lượt tra từ theo NGÀY.
 *
 * Khác quota chấm Writing/Speaking (đếm trọn đời, admin cộng thêm tay): tra từ
 * là thao tác vặt, dùng hàng chục lần mỗi buổi học, nên hạn mức phải tự làm
 * mới mỗi ngày. Dòng cũ giữ lại để về sau dựng báo cáo mức dùng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocab_lookup_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('usage_date');
            $table->unsignedInteger('count')->default(0);
            // Trong `count` có bao nhiêu lượt thực sự gọi OpenAI (phần còn lại
            // trúng kho đệm). Không có số này thì không ước lượng được chi phí.
            $table->unsignedInteger('api_calls')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocab_lookup_usages');
    }
};
