<?php

namespace App\Console\Commands;

use App\Mail\ClassSessionReminderMail;
use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Gửi email nhắc học viên trước giờ lớp online.
 *
 * Chỉ nhắc buổi CÓ ĐẶT giờ bắt đầu — buổi "mở tự do" không có mốc nào để nhắc.
 * Mỗi buổi chỉ gửi MỘT lần (cột `reminder_sent_at`): cron chạy mỗi phút, thiếu
 * cờ này là spam hàng trăm email mỗi phút và có thể bị Gmail chặn.
 */
class SendClassReminders extends Command
{
    protected $signature = 'classes:remind {--minutes=60 : Nhắc trước giờ học bao nhiêu phút}';

    protected $description = 'Gửi email nhắc học viên sắp tới giờ lớp online';

    public function handle(): int
    {
        $truoc = (int) $this->option('minutes');

        $sessions = ClassSession::where('is_active', true)
            ->whereNull('reminder_sent_at')
            ->whereNotNull('starts_at')
            ->where('starts_at', '>', now())                     // chưa bắt đầu
            ->where('starts_at', '<=', now()->addMinutes($truoc)) // sắp tới trong cửa sổ
            ->get();

        if ($sessions->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($sessions as $session) {
            // Đánh dấu TRƯỚC khi gửi. Nếu gửi giữa chừng lỗi, thà sót một buổi
            // còn hơn lượt cron sau gửi lại từ đầu cho những người đã nhận.
            $session->update(['reminder_sent_at' => now()]);

            $nhan = 0;
            User::invitableToClass()->get(['id', 'email'])->each(function ($u) use ($session, &$nhan) {
                try {
                    Mail::to($u->email)->send(new ClassSessionReminderMail($session));
                    $nhan++;
                } catch (\Throwable $e) {
                    // Một địa chỉ hỏng không được làm chết cả đợt gửi.
                    report($e);
                }
            });

            $this->info("Đã nhắc {$nhan} học viên về buổi: {$session->title}");
        }

        return self::SUCCESS;
    }
}
