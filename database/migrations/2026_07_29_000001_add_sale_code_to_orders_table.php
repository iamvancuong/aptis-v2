<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gắn mã sale giới thiệu vào đơn (attribution). Lưu chuỗi mã (denormalized) để
 * lịch sử vẫn giữ khi sale bị gỡ khỏi config. Index phục vụ thống kê doanh số.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('sale_code', 16)->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sale_code']);
            $table->dropColumn('sale_code');
        });
    }
};
