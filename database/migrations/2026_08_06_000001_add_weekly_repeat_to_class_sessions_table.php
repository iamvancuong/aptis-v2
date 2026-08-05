<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buổi học lặp lại hằng tuần.
 *
 * Lịch dạy thật là sự kiện LẶP trên Google Calendar (một link Meet dùng cả
 * khoá), nhưng `class_sessions` chỉ biết mốc thời gian cụ thể — nên mỗi tuần
 * admin phải tạo tay lại từng buổi. 5 buổi/tuần ≈ 260 buổi/năm gõ lại.
 *
 * Cách làm: KHÔNG thêm bảng "lịch" riêng. Buổi học nào bật `repeat_weekly` thì
 * chính nó là buổi gốc, và `classes:generate-sessions` sinh các buổi con cùng
 * thứ/giờ cho những tuần sắp tới. Buổi con là ClassSession bình thường nên mọi
 * thứ đã có (quyền vào, nhật ký, email nhắc, khách mời riêng) chạy nguyên,
 * không phải sửa chỗ nào — đó là lý do chọn hướng này thay vì bảng lịch mới.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->boolean('repeat_weekly')->default(false)->after('is_active');

            // Buổi con trỏ về buổi gốc. `nullOnDelete` chứ KHÔNG cascade: buổi con
            // là buổi đã/sắp dạy thật, có nhật ký vào lớp của học viên. Xoá buổi
            // gốc mà kéo theo cả lịch sử là mất dữ liệu thật vì một thao tác dọn
            // lịch. Xoá gốc = dừng lặp, các buổi đã sinh vẫn nguyên.
            $table->foreignId('repeat_source_id')->nullable()->after('repeat_weekly')
                ->constrained('class_sessions')->nullOnDelete();

            // Chống sinh trùng bằng CẤU TRÚC DỮ LIỆU, không bằng việc lệnh nhớ
            // kiểm tra trước. Lệnh chạy hai lần trong ngày, hay cron chồng nhau,
            // đều không tạo được buổi thứ hai cho cùng một mốc.
            //
            // Buổi tự tạo tay có `repeat_source_id` = NULL, mà NULL không tham gia
            // ràng buộc unique ở cả MySQL lẫn SQLite — nên hai buổi thủ công trùng
            // giờ vẫn tạo được như trước. Hành vi cũ không đổi.
            $table->unique(['repeat_source_id', 'starts_at'], 'class_sessions_repeat_unique');

            // Lệnh sinh buổi quét đúng cột này.
            $table->index('repeat_weekly');
        });
    }

    public function down(): void
    {
        Schema::table('class_sessions', function (Blueprint $table) {
            $table->dropUnique('class_sessions_repeat_unique');
            $table->dropIndex(['repeat_weekly']);
            $table->dropConstrainedForeignId('repeat_source_id');
            $table->dropColumn('repeat_weekly');
        });
    }
};
