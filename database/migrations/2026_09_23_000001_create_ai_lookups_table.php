<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kho đệm kết quả tra từ.
 *
 * Học viên bôi trùng từ rất nhiều ("however", "implement", "in order to"...).
 * Mỗi dòng ở đây là một lượt KHÔNG phải trả tiền cho OpenAI nữa. Sau vài tháng
 * bảng này chính là cuốn từ điển APTIS riêng của hệ thống.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_lookups', function (Blueprint $table) {
            $table->id();

            // sha256(mode + '|' + normalized_term). KHÔNG đưa câu ngữ cảnh vào
            // khoá: mỗi câu một khác thì cache gần như không bao giờ trúng.
            // Đánh đổi: từ đa nghĩa có thể trả nghĩa của lần tra đầu tiên —
            // chấp nhận được với từ đơn, và cụm/câu thì đã tra nguyên văn rồi.
            $table->string('hash', 64)->unique();

            $table->string('term', 500);
            $table->string('mode', 10)->default('word'); // word | phrase
            $table->json('payload');

            // Đếm lượt trúng đệm: vừa để biết từ nào học viên hay vướng, vừa là
            // cơ sở báo cáo "đã tiết kiệm bao nhiêu lượt gọi API".
            $table->unsignedInteger('hit_count')->default(1);

            $table->timestamps();

            $table->index('term');
            $table->index('hit_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_lookups');
    }
};
