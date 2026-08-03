<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mốc vi phạm gần nhất — để `violation_count` hết hạn được.
 *
 * Không có cột này thì `violation_count` chỉ có tăng, không bao giờ giảm: một lần
 * vi phạm tháng 3 cộng một lần tháng 8 là đủ khoá tài khoản. Chủ dự án muốn luật
 * "cảnh báo lần đầu, cố tình lần nữa thì khoá" — chữ "cố tình" hàm ý hai lần GẦN
 * NHAU, nên phải biết lần trước cách đây bao lâu.
 *
 * Để null cho dữ liệu cũ: `violation_count` sẵn có không biết xảy ra khi nào, và
 * lệnh `devices:apply-policy` sẽ reset hết về 0 khi bật luật mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_violation_at')->nullable()->after('violation_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_violation_at');
        });
    }
};
