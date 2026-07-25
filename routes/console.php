<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tự động tạo phòng Zoom + gửi link trước mỗi buổi hướng dẫn.
// Cần cron trên server: * * * * * php artisan schedule:run
Schedule::command('guidance:dispatch')->hourly();

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
