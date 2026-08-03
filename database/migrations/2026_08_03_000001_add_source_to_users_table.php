<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nguồn gốc tài khoản: tự mua qua PayOS, admin tạo tay, hay nhập từ dữ liệu cũ.
 *
 * ⚠️ Vì sao phải là CỘT chứ không truy ra từ bảng `orders`: luồng PayOS mới chạy
 * từ 28/07/2026, nên tại thời điểm thêm cột này chỉ có 2/848 tài khoản có đơn đã
 * thanh toán. Suy "không có đơn ⇒ tạo tay" sẽ xếp nhầm gần như toàn bộ học viên
 * cũ. Dữ liệu cũ phải backfill bằng tay: `php artisan users:backfill-source`.
 *
 * Giá trị mặc định 'manual' chỉ đúng cho tài khoản TẠO MỚI từ khu admin. Hai chỗ
 * tạo tài khoản đều gán tường minh (OrderFulfillmentService, Admin\UserController)
 * — default ở đây là lưới an toàn, không phải nguồn sự thật.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('role')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn('source');
        });
    }
};
