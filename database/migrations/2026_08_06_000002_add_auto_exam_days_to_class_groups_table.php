<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lớp tự gom học viên sắp thi ("Nhóm thi tuần này").
 *
 * Ô "Ngày thi (Exam Date)" ở form tạo user lưu vào `users.expires_at` — không có
 * cột ngày thi riêng. Nên "ai thi trong tuần tới" là câu hỏi trả lời được ngay
 * bằng dữ liệu đang có, không phải thu thập thêm gì.
 *
 * `auto_exam_days` = số ngày tới. NULL = lớp thường, thành viên do admin chọn tay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_groups', function (Blueprint $table) {
            $table->unsignedSmallInteger('auto_exam_days')->nullable()->after('source_filter');
        });
    }

    public function down(): void
    {
        Schema::table('class_groups', function (Blueprint $table) {
            $table->dropColumn('auto_exam_days');
        });
    }
};
