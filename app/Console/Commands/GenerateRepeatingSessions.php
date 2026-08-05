<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sinh các buổi học của lịch lặp hằng tuần.
 *
 * Lịch dạy thật nằm trên Google Calendar dưới dạng sự kiện LẶP (một link Meet
 * dùng cả khoá). Web thì mỗi buổi là một mốc thời gian cụ thể, nên không có
 * lệnh này admin phải gõ lại 5 buổi mỗi tuần, quanh năm.
 *
 * Chạy hằng ngày (routes/console.php). Sinh trùng là không thể: cặp
 * (repeat_source_id, starts_at) có ràng buộc unique, nên cron chạy chồng hay
 * admin bấm lại đều không tạo được buổi thứ hai cho cùng một mốc.
 */
class GenerateRepeatingSessions extends Command
{
    /** Chặn vòng lặp chạy vô hạn nếu buổi gốc có `starts_at` ở quá khứ rất xa. */
    private const TOI_DA_BUOC = 1000;

    protected $signature = 'classes:generate-sessions
                            {--weeks=4 : Sinh trước bao nhiêu tuần}
                            {--dry-run : Chỉ in ra, không tạo buổi nào}';

    protected $description = 'Sinh buổi học cho các lịch lặp hằng tuần';

    public function handle(): int
    {
        $soTuan = max(1, (int) $this->option('weeks'));
        $thu = (bool) $this->option('dry-run');

        // Buổi gốc phải ĐANG BẬT: tắt buổi gốc là cách dừng một lịch lặp mà không
        // phải xoá nó (xoá sẽ mất luôn liên kết của các buổi đã sinh).
        $goc = ClassSession::query()
            ->where('repeat_weekly', true)
            ->where('is_active', true)
            ->whereNotNull('starts_at')
            ->withCount('extraMembers')
            ->orderBy('starts_at')
            ->get();

        if ($goc->isEmpty()) {
            $this->line('Chưa có buổi học nào bật "Lặp lại hằng tuần".');

            return self::SUCCESS;
        }

        $tuNgay = now()->startOfDay();
        $denNgay = now()->addWeeks($soTuan)->endOfDay();

        $this->line(sprintf(
            'Sinh buổi từ %s đến %s (%d tuần)%s',
            $tuNgay->format('d/m/Y'),
            $denNgay->format('d/m/Y'),
            $soTuan,
            $thu ? ' — CHẠY THỬ, không ghi gì' : '',
        ));
        $this->newLine();

        $tongMoi = 0;

        foreach ($goc as $buoi) {
            $tongMoi += $this->sinhChoBuoi($buoi, $tuNgay, $denNgay, $thu);
        }

        $this->newLine();
        $this->line($thu
            ? "Sẽ tạo {$tongMoi} buổi. Bỏ --dry-run để tạo thật."
            : "Đã tạo {$tongMoi} buổi.");

        return self::SUCCESS;
    }

    private function sinhChoBuoi(ClassSession $goc, Carbon $tuNgay, Carbon $denNgay, bool $thu): int
    {
        $this->line("<comment>━━━ #{$goc->id} — {$goc->title}</comment> ("
            . $goc->starts_at->locale('vi')->dayName . ', ' . $goc->starts_at->format('H:i') . ')');

        // Khách mời riêng cố ý KHÔNG được sao chép sang buổi mới: đó là ngoại lệ
        // MỘT LẦN (học bù, học thử). Copy tự động sẽ âm thầm cấp quyền vĩnh viễn
        // cho người chỉ được mời một buổi — mở quyền im lặng là kiểu lỗi không ai
        // phát hiện ra. Nói thẳng để admin mời lại tay nếu thật sự cần.
        if ($goc->extra_members_count > 0) {
            $this->line("    ⚠️ Buổi gốc có {$goc->extra_members_count} khách mời riêng —"
                . ' KHÔNG sao chép sang buổi mới. Mời lại tay nếu cần.');
        }

        $keoDai = $goc->ends_at
            ? $goc->starts_at->diffInSeconds($goc->ends_at)
            : null;

        $moi = 0;

        foreach ($this->cacMoc($goc->starts_at, $tuNgay, $denNgay) as $moc) {
            if ($thu) {
                $this->line('    + ' . $moc->format('H:i d/m/Y') . ' (chạy thử)');
                $moi++;

                continue;
            }

            $buoi = ClassSession::firstOrCreate(
                ['repeat_source_id' => $goc->id, 'starts_at' => $moc],
                [
                    'title'          => $goc->title,
                    'description'    => $goc->description,
                    'meet_link'      => $goc->meet_link,
                    'class_group_id' => $goc->class_group_id,
                    'ends_at'        => $keoDai === null ? null : $moc->copy()->addSeconds($keoDai),
                    'is_active'      => true,
                    // Buổi con KHÔNG tự lặp tiếp, nếu không mỗi buổi sinh ra lại
                    // thành một gốc mới và số buổi tăng theo cấp số nhân.
                    'repeat_weekly'  => false,
                ],
            );

            if ($buoi->wasRecentlyCreated) {
                $this->line('    + ' . $moc->format('H:i d/m/Y') . " → buổi #{$buoi->id}");
                $moi++;
            }
        }

        if ($moi === 0) {
            $this->line('    (đã đủ buổi, không tạo thêm)');
        }

        return $moi;
    }

    /**
     * Các mốc bắt đầu nằm trong khoảng cần sinh, cách nhau đúng 7 ngày kể từ
     * buổi gốc. Giữ nguyên giờ trong ngày — Việt Nam không đổi giờ theo mùa nên
     * cộng tuần không làm lệch giờ học.
     *
     * @return list<Carbon>
     */
    private function cacMoc(Carbon $goc, Carbon $tuNgay, Carbon $denNgay): array
    {
        $moc = $goc->copy();
        $buoc = 0;

        // Nhảy qua các tuần đã trôi qua trước, rồi mới thu thập.
        while ($moc->lt($tuNgay) && $buoc++ < self::TOI_DA_BUOC) {
            $moc->addWeek();
        }

        $ket = [];

        while ($moc->lte($denNgay) && $buoc++ < self::TOI_DA_BUOC) {
            // Không sinh lại chính buổi gốc.
            if (! $moc->eq($goc)) {
                $ket[] = $moc->copy();
            }

            $moc->addWeek();
        }

        return $ket;
    }
}
