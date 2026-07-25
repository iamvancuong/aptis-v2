<?php

namespace App\Console\Commands;

use App\Models\GuidanceBooking;
use App\Models\GuidanceSession;
use App\Services\GuidanceSessionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Tự động tạo phòng Zoom + gửi link cho các buổi sắp diễn ra (trong khoảng
 * `send_before_hours` trước giờ học) mà chưa gửi. Chạy định kỳ (cron).
 */
class SendGuidanceLinks extends Command
{
    protected $signature = 'guidance:dispatch';
    protected $description = 'Tạo phòng Zoom và gửi link cho các buổi hướng dẫn sắp tới';

    public function handle(GuidanceSessionService $service): int
    {
        $dates = GuidanceBooking::whereDate('session_date', '>=', today())
            ->distinct()
            ->pluck('session_date');

        $window = (int) config('guidance.send_before_hours');
        $sent   = 0;

        foreach ($dates as $date) {
            $startAt  = Carbon::parse($date->toDateString() . ' ' . config('guidance.time'));
            $sendFrom = $startAt->copy()->subHours($window);

            // Chỉ gửi trong cửa sổ trước buổi và chưa gửi lần nào.
            if (! now()->betweenIncluded($sendFrom, $startAt)) {
                continue;
            }

            $session = GuidanceSession::whereDate('session_date', $date->toDateString())->first();
            if ($session && $session->sent_at) {
                continue;
            }

            $result = $service->activateAndSend(Carbon::parse($date->toDateString()));
            $sent++;
            $this->info("Đã gửi buổi {$date->toDateString()} cho {$result['sent']} học viên.");
        }

        $this->info("Hoàn tất: {$sent} buổi được gửi.");

        return self::SUCCESS;
    }
}
