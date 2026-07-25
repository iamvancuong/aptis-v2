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
