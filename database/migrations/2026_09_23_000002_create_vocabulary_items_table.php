<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sổ tay từ vựng của từng học viên + thư mục + trạng thái ôn tập kiểu Anki.
 *
 * Cố ý gộp nội dung nghĩa vào đây thay vì chỉ trỏ sang `ai_lookups`: học viên
 * sửa lại nghĩa cho dễ nhớ là chuyện thường, và sổ tay không được đổi nội dung
 * chỉ vì kho đệm chung được cập nhật.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Thư mục do học viên tự tạo. Thư mục theo loại từ (danh từ, động từ…)
        // KHÔNG nằm ở đây — đó là bộ lọc tự động theo cột `word_type`.
        Schema::create('vocab_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('vocabulary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Mỗi từ thuộc tối đa MỘT thư mục tự tạo (như deck của Anki). Xoá
            // thư mục thì từ vẫn còn, chỉ mất thư mục.
            $table->foreignId('folder_id')->nullable()->constrained('vocab_folders')->nullOnDelete();

            $table->string('term', 500);                 // nguyên văn học viên bôi
            $table->string('normalized_term', 500);      // lowercase + gọn khoảng trắng
            $table->text('meaning');                     // nghĩa tiếng Việt
            // Mã loại từ cố định (noun, verb, … xem VocabularyItem::WORD_TYPES)
            // để chia thư mục tự động. `part_of_speech` là nhãn tiếng Việt AI
            // trả về, có thể lộn xộn ("động từ (nội)") nên không dùng để lọc.
            $table->string('word_type', 20)->nullable();
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

            /* ── Trạng thái ôn tập: kiểu Anki (xem VocabularyItem::schedule) ── */
            $table->string('srs_state', 12)->default('new'); // new | learning | review | relearning
            $table->unsignedTinyInteger('step')->default(0); // bước hiện tại trong learning_steps
            $table->unsignedSmallInteger('interval_days')->default(0);
            $table->unsignedSmallInteger('ease')->default(2500); // ×1000
            $table->unsignedSmallInteger('lapses')->default(0);  // số lần quên sau khi đã thuộc
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);

            $table->timestamps();

            // Bôi lại từ đã lưu thì cập nhật, không tạo dòng thứ hai.
            $table->unique(['user_id', 'normalized_term']);
            // Truy vấn nóng nhất: "từ nào của tôi tới hạn ôn".
            $table->index(['user_id', 'due_at']);
            $table->index(['user_id', 'word_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_items');
        Schema::dropIfExists('vocab_folders');
    }
};
