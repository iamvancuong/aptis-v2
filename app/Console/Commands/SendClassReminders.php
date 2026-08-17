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
        // Tính năng đang hoãn. Không chặn ở đây thì cron vẫn bắn email nhắc giờ
        // kèm nút "Vào lớp" trỏ tới URL đang trả 404 — học viên nhận mail về một
        // tính năng họ không nhìn thấy ở đâu cả.
        if (! config('aptis.classes_enabled')) {
            $this->info('Lớp học online đang tắt (aptis.classes_enabled) — bỏ qua.');

            return self::SUCCESS;
        }

        $truoc = (int) $this->option('minutes');

        $sessions = ClassSession::with('classGroup')       // `forClassSession` đọc lớp
            ->where('is_active', true)
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

            // ⚠️ CHỈ nhắc thành viên của buổi, không phải toàn trường. Dùng
            // `invitableToClass()` ở đây là gửi mail cho hàng trăm người về một
            // buổi họ không được vào — lỗi loại này chỉ lộ ra sau khi đã gửi.
            $nhan = 0;
            User::forClassSession($session)->get(['id', 'name', 'email'])->each(function ($u) use ($session, &$nhan) {
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
