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

// Dọn file ghi âm bài Nói CŨ HƠN 2 THÁNG (60 ngày) — chính sách giữ audio 2
// tháng rồi xoá để đỡ đầy ổ (hosting NVMe có hạn, audio tích luỹ mãi).
//
// ⚠️ Lệnh này XOÁ AUDIO THẬT, không khôi phục được. Đã có sẵn ba lớp chắn trong
// CleanupSpeakingAudio: chỉ đụng file cũ hơn --days, KHÔNG xoá phần chưa chấm
// xong, và từ chối chạy nếu --days < 30. Chạy 03:15 mỗi ngày (sau các job lớp
// lúc 03:00/03:30 nhưng vẫn giờ vắng) để disk luôn được giữ gọn.
//
// Muốn xem trước sẽ xoá gì mà không xoá thật:
//   php artisan speaking:cleanup-audio --days=60 --dry-run
Schedule::command('speaking:cleanup-audio --days=60')
    ->dailyAt('03:15')
    ->withoutOverlapping();

// Vét file ghi âm MỒ CÔI (không còn attempt_answer nào trỏ tới) — lưới an toàn
// cho audio còn sót của user đã xoá trước khi có bản vá xoá-user-xoá-audio, hoặc
// của lần xoá tay trên cPanel. Từ nay xoá user đã tự dọn file (UserController::
// destroy), nên đây chỉ chạy hằng tuần cho chắc, không cần dày.
Schedule::command('speaking:cleanup-orphan-audio')
    ->weeklyOn(1, '03:45')
    ->withoutOverlapping();
