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
//
// ⚠️ BÀI HỌC 27/08/2026 — MỘT WORKER TUẦN TỰ LÀ KHÔNG ĐỦ.
// Hôm đó tồn 335 job (≈84 bài Nói) mà KHÔNG có job nào lỗi: worker vẫn chạy
// đúng, chỉ là không kịp. Một job Nói mất 10–20 giây, một worker rút được ~3
// job/phút, nên một đợt nộp bài là học viên chờ gần 2 tiếng mới thấy điểm.
// Hàng đợi tồn mà 0 job thất bại = vấn đề CÔNG SUẤT, không phải bug.
//
// Hai thay đổi chữa gốc:
//   ① Tách hàng `speaking` khỏi `default` — job Writing (vài giây) không còn
//      xếp sau hàng trăm job Nói (xem SpeakingAiDispatcher).
//   ② Chạy nhiều worker Nói song song. Job Nói phần lớn là CHỜ MẠNG (gọi
//      OpenAI) chứ không ăn CPU, nên song song gần như miễn phí với shared
//      hosting 2 core — khác hẳn việc chạy song song một việc nặng CPU.

// Hàng mặc định: Writing + việc lặt vặt. Nhanh, giữ riêng một worker để không
// bao giờ bị kẹt sau bài Nói.
Schedule::command('queue:work --queue=default --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Worker cho bài Nói. `--queue=speaking,default` (theo thứ tự ưu tiên): ưu tiên
// hàng Nói, hết việc thì phụ rút hàng mặc định — nhờ vậy đống job Nói CŨ còn
// nằm ở hàng `default` (đẩy trước khi có thay đổi này) vẫn được dọn, không cần
// đụng tay vào bảng `jobs`.
//
// `--name` phải KHÁC nhau: `withoutOverlapping()` khoá theo chuỗi lệnh, hai lệnh
// giống hệt nhau sẽ dùng chung một khoá và con thứ hai không bao giờ chạy.
foreach (range(1, (int) config('queue.speaking_workers')) as $i) {
    Schedule::command(
        "queue:work --queue=speaking,default --stop-when-empty --max-time=50 --tries=3 --name=speaking{$i}"
    )->everyMinute()->withoutOverlapping();
}

// Nhắc học viên trước giờ lớp online 60 phút. Mỗi buổi chỉ gửi một lần
// (cột `class_sessions.reminder_sent_at`) nên chạy dày cũng không spam.
Schedule::command('classes:remind')->everyFiveMinutes()->withoutOverlapping();

// Sinh buổi học của các lịch lặp hằng tuần, luôn giữ sẵn 4 tuần phía trước.
// Chạy hằng ngày chứ không hằng tuần: cron lỡ một nhịp (server bảo trì, hosting
// treo) thì hôm sau bù ngay, thay vì để trống một tuần mà không ai biết. Sinh
// trùng là không thể — cặp (repeat_source_id, starts_at) có ràng buộc unique.
Schedule::command('classes:generate-sessions')->dailyAt('03:30')->withoutOverlapping();

// Cập nhật thành viên lớp "tự gom theo ngày thi" (Nhóm thi tuần này). Chạy TRƯỚC
// giờ dạy trong ngày để danh sách luôn đúng; người vừa qua ngày thi rơi khỏi lớp.
// ⚠️ Chỉ đồng bộ phía WEB — lời mời Google Calendar vẫn phải dán tay (GĐ3 mới vá).
Schedule::command('classes:sync-exam-groups')->dailyAt('03:00')->withoutOverlapping();

// Dọn phiên đăng nhập chết từ lâu (mỗi lần học viên xoá cookie là một dòng ở lại
// vĩnh viễn). An toàn: phép đếm thiết bị đã lọc theo cửa sổ hoạt động nên xoá các
// dòng này không mở thêm quyền cho ai.
Schedule::command('sessions:prune')->dailyAt('04:00')->withoutOverlapping();

// ⚠️ CỐ Ý ĐỂ TẮT. Dọn file ghi âm bài Nói cũ (hosting 30GB chia 21 web).
// Lệnh này XOÁ AUDIO THẬT CỦA HỌC VIÊN và không khôi phục được, nên không tự
// bật. Chạy thử trước rồi mới bỏ comment dòng dưới:
//
//   php artisan speaking:cleanup-audio --dry-run
//
// Schedule::command('speaking:cleanup-audio')->weeklyOn(1, '03:00')->withoutOverlapping();
