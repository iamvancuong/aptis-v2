<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gmail học viên dùng để vào lớp online.
 *
 * Vì sao cần cột riêng, không dùng luôn `users.email`: danh tính Milaedu và
 * danh tính Google là HAI thứ khác nhau. Học viên đăng ký Milaedu bằng email
 * bất kỳ, nhưng vào Google Meet lại bằng tài khoản Google họ đang đăng nhập.
 * Không có cột này thì không cách nào mời đúng người qua Google Calendar.
 *
 * Nullable vì học viên tự điền — chưa điền thì vẫn học bình thường, chỉ là
 * phải xin duyệt thủ công khi vào phòng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_email')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_email');
        });
    }
};
