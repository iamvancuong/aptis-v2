<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `device_id` đang unique TOÀN CỤC — một trình duyệt chỉ được nằm trong đúng một
 * dòng, dù nhiều tài khoản cùng đăng nhập trên đó.
 *
 * Chính ràng buộc này đẻ ra lỗ hổng đếm thiết bị: khi tài khoản B đăng nhập trên
 * máy đã có dòng của tài khoản A, code buộc phải "cướp" dòng đó gán sang B rồi
 * thoát sớm — bỏ qua luôn phép đếm thiết bị của B. Bằng chứng trong DB: có tài
 * khoản giữ 4 dòng dù trần là 3.
 *
 * Đổi sang unique theo CẶP (device_id, user_id): một máy dùng chung nhà (bố mẹ,
 * anh em) giữ được một dòng cho mỗi tài khoản, và không còn lý do gì để cướp dòng
 * của nhau nữa. Dữ liệu hiện tại không thể có trùng (đang unique đơn) nên đổi an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_sessions', function (Blueprint $table) {
            $table->dropUnique(['device_id']);
            $table->unique(['device_id', 'user_id']);
            // Phép đếm mới lọc theo mốc hoạt động, chạy mỗi request của mỗi học viên.
            $table->index(['user_id', 'last_active_at']);
        });
    }

    public function down(): void
    {
        Schema::table('login_sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'last_active_at']);
            $table->dropUnique(['device_id', 'user_id']);
            $table->unique('device_id');
        });
    }
};
