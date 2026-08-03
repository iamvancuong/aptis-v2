<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Một địa chỉ Google chỉ được gắn với MỘT tài khoản Milaedu.
 *
 * Vì sao cần: việc đối chiếu điểm danh sau buổi học lấy Gmail làm khoá nối giữa
 * hai hệ danh tính (Milaedu ↔ Google). Nếu ba anh em cùng khai một Gmail thì
 * trong báo cáo điểm danh họ gộp thành một dòng, và dấu hiệu "một tài khoản ở
 * hai nơi cùng lúc" không còn phát hiện được gì.
 *
 * Thời điểm chạy được chọn có chủ đích: kiểm tra trước khi viết migration này
 * cho thấy 0/848 tài khoản đã khai `google_email`, nên chưa có dữ liệu trùng để
 * phải dọn. Càng để lâu càng đắt.
 *
 * NULL không bị ràng buộc unique (cả MySQL lẫn SQLite) — người chưa khai vẫn
 * lưu bình thường, và đó là đa số.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('google_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_email']);
        });
    }
};
