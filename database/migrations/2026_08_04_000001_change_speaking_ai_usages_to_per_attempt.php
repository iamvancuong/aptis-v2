<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Đổi đơn vị đếm lượt chấm Nói bằng AI: từ MỖI PHẦN sang MỖI BÀI.
 *
 * Bản đầu trừ lượt cho từng phần có ghi âm (`recordSpeakingAiUsage($part)` gọi
 * trong vòng lặp). Đề Nói có 4 phần nên **một bài nộp tiêu 4 lượt** — hạn mức 10
 * thực ra chỉ được 2 bài rưỡi, không ai đoán ra điều đó từ con số 10.
 * Chủ dự án chốt: **10 lượt = 10 BÀI**.
 *
 * `attempt_id` nằm trong unique nên nộp lại cùng một bài KHÔNG trừ lượt lần hai —
 * chống trùng bằng cấu trúc dữ liệu, không phải bằng câu lệnh if ở tầng ứng dụng.
 *
 * `speaking_part` chuyển thành nullable và không còn được ghi. Cố ý KHÔNG xoá cột:
 * dữ liệu cũ (đếm theo phần) vẫn đọc được để tra cứu. Chúng không ảnh hưởng phép
 * đếm mới vì khi bật luật mới sẽ nâng `users.speaking_ai_reset_version` — mọi dòng
 * cũ nằm ở version trước nên tự động không được tính.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tách từng lệnh: trên SQLite, đổi cột / đổi unique đều dựng lại bảng,
        // gộp chung một closure thì thứ tự thao tác không đảm bảo.
        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'speaking_part', 'reset_version']);
        });

        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->unsignedTinyInteger('speaking_part')->nullable()->change();
        });

        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->foreignId('attempt_id')->nullable()->after('user_id')
                ->constrained()->cascadeOnDelete();
        });

        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->unique(['user_id', 'attempt_id', 'reset_version']);
        });
    }

    public function down(): void
    {
        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'attempt_id', 'reset_version']);
        });

        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->dropForeign(['attempt_id']);
            $table->dropColumn('attempt_id');
        });

        Schema::table('speaking_ai_usages', function (Blueprint $table) {
            $table->unique(['user_id', 'speaking_part', 'reset_version']);
        });
    }
};
