<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sổ tay từ vựng của từng học viên + trạng thái ôn tập (spaced repetition).
 *
 * Cố ý gộp nội dung nghĩa vào đây thay vì chỉ trỏ sang `ai_lookups`: học viên
 * sửa lại nghĩa cho dễ nhớ là chuyện thường, và sổ tay không được đổi nội dung
 * chỉ vì kho đệm chung được cập nhật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('term', 500);                 // nguyên văn học viên bôi
            $table->string('normalized_term', 500);      // lowercase + gọn khoảng trắng
            $table->text('meaning');                     // nghĩa tiếng Việt
            $table->string('part_of_speech', 50)->nullable();
            $table->string('phonetic', 100)->nullable();
            $table->text('example')->nullable();
            $table->string('cefr', 5)->nullable();

            // Câu gốc trong bài đọc. Đây là thứ làm sổ tay này khác một danh
            // sách từ khô khan: học viên nhớ từ theo ngữ cảnh đã gặp.
            $table->text('context_sentence')->nullable();

            $table->string('source_skill', 20)->nullable();
            $table->unsignedTinyInteger('source_part')->nullable();
            // KHÔNG đặt khoá ngoại sang `sets`: xoá một bộ đề cũ không được phép
            // kéo theo từ vựng học viên đã lưu.
            $table->unsignedBigInteger('source_set_id')->nullable();

            /* ── Trạng thái ôn tập: Leitner 5 hộp ── */
            $table->unsignedTinyInteger('box')->default(1);
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            // Chuỗi nhớ đúng liên tiếp — dùng để đánh dấu "đã thuộc".
            $table->unsignedSmallInteger('streak')->default(0);

            $table->timestamps();

            // Bôi lại từ đã lưu thì cập nhật, không tạo dòng thứ hai.
            $table->unique(['user_id', 'normalized_term']);
            // Truy vấn nóng nhất: "từ nào của tôi tới hạn ôn hôm nay".
            $table->index(['user_id', 'due_at']);
            $table->index(['user_id', 'box']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_items');
    }
};
