<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Lưới an toàn: đối soát đơn pending với PayOS (phòng khi webhook không tới).
Schedule::command('payos:reconcile')->everyTwoMinutes()->withoutOverlapping();

// Rút hàng đợi job (chấm Writing/Speaking AI tự động dispatch khi nộp mock/practice).
// QUEUE_CONNECTION=database mà không có worker chạy nền thì job nằm chết trong bảng
// jobs → bài không bao giờ được chấm. Trên cPanel chỉ có 1 cron `schedule:run`, nên
// mỗi phút rút sạch hàng đợi rồi thoát (--stop-when-empty), giới hạn thời gian chạy
// để không đè lượt sau (--max-time), và không chạy chồng (withoutOverlapping).
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Nhắc học viên trước giờ lớp online 60 phút. Mỗi buổi chỉ gửi một lần
// (cột `class_sessions.reminder_sent_at`) nên chạy dày cũng không spam.
Schedule::command('classes:remind')->everyFiveMinutes()->withoutOverlapping();

// ⚠️ CỐ Ý ĐỂ TẮT. Dọn file ghi âm bài Nói cũ (hosting 30GB chia 21 web).
// Lệnh này XOÁ AUDIO THẬT CỦA HỌC VIÊN và không khôi phục được, nên không tự
// bật. Chạy thử trước rồi mới bỏ comment dòng dưới:
//
//   php artisan speaking:cleanup-audio --dry-run
//
// Schedule::command('speaking:cleanup-audio')->weeklyOn(1, '03:00')->withoutOverlapping();
