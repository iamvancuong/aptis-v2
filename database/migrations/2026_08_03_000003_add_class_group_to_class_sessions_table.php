<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gắn buổi học vào một lớp, và mở đường cho "khách mời thêm" của riêng một buổi.
 *
 * `class_group_id` NULLABLE có chủ đích:
 *   null    = buổi mở cho MỌI học viên còn hạn (hành vi Pha 0, giữ nguyên cho
 *             các buổi đã tạo trước migration này + dùng cho workshop/buổi demo)
 *   có giá trị = chỉ thành viên lớp đó (∪ khách mời thêm của buổi)
 *
 * Dùng `restrictOnDelete` chứ KHÔNG phải `nullOnDelete`: xoá nhầm một lớp mà để
 * `null` thì mọi buổi của lớp đó lập tức mở cho toàn bộ học viên — một thao tác
 * xoá lại biến thành lộ quyền truy cập, im lặng. Chặn xoá và bắt admin dọn buổi
 * trước thì ồn ào hơn nhưng không bao giờ mở rộng quyền ngoài ý muốn.
 *
 * `meet_link` của buổi chuyển sang NULLABLE để kế thừa link của lớp.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hai lệnh TÁCH RIÊNG, không gộp một closure: trên SQLite, `change()` được
        // hiện thực bằng cách dựng lại bảng. Gộp chung với việc thêm khoá ngoại
        // trong cùng một batch thì thứ tự thao tác không đảm bảo.
        Schema::table('class_sessions', function (Blueprint $table) {
            // Trống = dùng link của lớp. Buổi không thuộc lớp nào thì bắt buộc có
            // link riêng — ràng buộc đó nằm ở tầng validate, không phải ở DB.
            $table->string('meet_link', 500)->nullable()->change();
        });

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->foreignId('class_group_id')->nullable()->after('id')
                ->constrained()->restrictOnDelete();
        });

        // Khách mời THÊM cho riêng một buổi (học thử, học bù). Chỉ chứa ngoại lệ
        // "cho phép" — muốn loại một người khỏi lớp thì gỡ họ khỏi lớp, không có
        // khái niệm "cấm riêng buổi". Hai chiều allow/deny nghe linh hoạt nhưng
        // sinh ra câu hỏi "cái nào thắng" mà admin không đoán được.
        Schema::create('class_session_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_session_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_user');

        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropForeign(['class_group_id']);
            $table->dropColumn('class_group_id');
        });
    }
};
