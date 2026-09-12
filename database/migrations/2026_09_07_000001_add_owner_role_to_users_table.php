<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm vai trò `owner` — chủ sở hữu hệ thống, toàn quyền như admin nhưng bị ẩn
 * khỏi mọi truy vấn của người khác (xem global scope `hideOwner` trong User).
 *
 * Dùng `->change()` của Laravel 11 (không cần doctrine/dbal): trên MySQL sinh
 * `MODIFY COLUMN ... ENUM(...)`, trên SQLite (dùng khi chạy test) tự dựng lại
 * bảng với ràng buộc CHECK mới cho phép 'owner'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin', 'owner'])->default('user')->change();
        });
    }

    public function down(): void
    {
        // Phải hạ mọi owner còn lại về admin TRƯỚC khi thu hẹp enum — nếu không
        // ràng buộc mới sẽ từ chối dữ liệu đang có và migration đổ.
        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['user', 'admin'])->default('user')->change();
        });
    }
};
