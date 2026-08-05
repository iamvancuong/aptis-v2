<?php

namespace App\Console\Commands;

use App\Models\ClassGroup;
use App\Models\ClassSession;
use App\Models\User;
use App\Support\MeetLink;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Dựng sẵn lớp + buổi học lặp hằng tuần từ một file lịch.
 *
 * Dựng tay qua giao diện là ~30 phút bấm cho 2 lớp và 6 buổi, mà mỗi buổi có
 * link phòng riêng, giờ riêng và một ô tick dễ quên ("Lặp lại hằng tuần"). Dán
 * nhầm link của lớp khác không báo lỗi gì — nó chỉ là rò phòng học.
 *
 * Lệnh KHÔNG phá dữ liệu: lớp/buổi đã có thì bỏ qua chứ không ghi đè, và thành
 * viên chỉ THÊM chứ không gỡ ai. Chạy lại nhiều lần đều an toàn.
 */
class ScaffoldClasses extends Command
{
    protected $signature = 'classes:scaffold
                            {--file= : File JSON mô tả lịch (mặc định database/data/lich-lop.json)}
                            {--dry-run : Chỉ in ra, không tạo gì}';

    protected $description = 'Dựng lớp + buổi học lặp hằng tuần từ file lịch';

    private const THU = [1 => 'Thứ Hai', 2 => 'Thứ Ba', 3 => 'Thứ Tư', 4 => 'Thứ Năm',
        5 => 'Thứ Sáu', 6 => 'Thứ Bảy', 7 => 'Chủ Nhật'];

    public function handle(): int
    {
        $duongDan = $this->option('file') ?: database_path('data/lich-lop.json');
        $thu = (bool) $this->option('dry-run');

        if (! is_readable($duongDan)) {
            $this->error("Không đọc được file lịch: {$duongDan}");

            return self::FAILURE;
        }

        $lich = json_decode(file_get_contents($duongDan), true);

        if (! is_array($lich)) {
            $this->error('File lịch không phải JSON hợp lệ: ' . json_last_error_msg());

            return self::FAILURE;
        }

        $this->line('Đọc lịch: ' . $duongDan . ($thu ? ' — CHẠY THỬ, không ghi gì' : ''));
        $this->newLine();

        $lop = $this->dungLop($lich['groups'] ?? [], $thu);
        $this->newLine();
        $this->dungBuoi($lich['sessions'] ?? [], $lop, $thu);

        $this->newLine();

        if ($thu) {
            $this->line('Chưa ghi gì. Bỏ --dry-run để dựng thật.');

            return self::SUCCESS;
        }

        $this->line('Xong. Ba việc còn lại KHÔNG lệnh nào làm thay được:');
        $this->line('  1. Vào /admin/class-groups/{id}/thanh-vien, copy danh sách mời của TỪNG lớp');
        $this->line('     rồi dán vào ô Khách mời của sự kiện Google Calendar tương ứng.');
        $this->line('  2. Trong mỗi phòng Meet: TẮT "Truy cập nhanh".');
        $this->line('  3. Trên Calendar: bỏ tick "Mời những người khác" + "Xem danh sách khách mời",');
        $this->line('     và đổi múi giờ sự kiện sang (GMT+07:00) Việt Nam.');
        $this->newLine();
        $this->line('Sinh buổi cho các tuần tới: php artisan classes:generate-sessions --dry-run');

        return self::SUCCESS;
    }

    /** @return array<string, ClassGroup> tên lớp → model, để phần buổi tra lại */
    private function dungLop(array $dinhNghia, bool $thu): array
    {
        $this->line('<comment>━━━ LỚP HỌC (nhóm người) ━━━</comment>');

        $ket = [];

        foreach ($dinhNghia as $g) {
            $ten = $g['name'] ?? null;

            if (! $ten) {
                $this->line('  <error>✗</error> Bỏ qua một lớp thiếu "name".');

                continue;
            }

            $daCo = ClassGroup::firstWhere('name', $ten);

            if ($daCo) {
                $this->line("  = {$ten} (đã có, giữ nguyên — {$daCo->members()->count()} thành viên)");
            } elseif ($thu) {
                $this->line("  + {$ten} (chạy thử)");

                // Model CHƯA LƯU, chỉ để phần buổi bên dưới tra được tên lớp.
                // Thiếu nó thì chạy thử trên hệ thống chưa có lớp nào sẽ báo
                // "không tìm thấy lớp" cho MỌI buổi — một kết quả sai, và chạy
                // thử mà nói sai thì không còn dùng để quyết định gì được nữa.
                $daCo = new ClassGroup(['name' => $ten]);
            } else {
                $daCo = ClassGroup::create([
                    'name'           => $ten,
                    'description'    => $g['description'] ?? null,
                    'source_filter'  => $g['source_filter'] ?? null,
                    'auto_exam_days' => $g['auto_exam_days'] ?? null,
                    // Link để mức LỚP trống có chủ đích: mỗi môn một phòng riêng
                    // nên link phải nằm ở BUỔI. Dán ở lớp thì mọi buổi kế thừa
                    // cùng một phòng — sai với lịch này.
                    'meet_link'      => null,
                    'is_active'      => true,
                ]);

                $this->line("  <info>+</info> {$ten} → lớp #{$daCo->id}");
            }

            $ket[$ten] = $daCo;
            $this->themThanhVien($daCo, $g, $thu);
        }

        return $ket;
    }

    private function themThanhVien(?ClassGroup $lop, array $g, bool $thu): void
    {
        $nguon = array_filter((array) ($g['members_from_sources'] ?? []));

        if ($lop === null || $nguon === []) {
            if (($g['auto_exam_days'] ?? null) > 0) {
                $this->line('      thành viên do lệnh classes:sync-exam-groups tự gom theo ngày thi');
            }

            return;
        }

        // Chỉ học viên CÒN HẠN: thêm người đã hết hạn vào lớp là mời họ vào một
        // cánh cửa mà `canJoinClassSession` vẫn đóng, và làm phồng danh sách mời.
        $ungVien = User::invitableToClass()->whereIn('source', $nguon)->pluck('id');

        if ($thu) {
            $this->line("      sẽ thêm {$ungVien->count()} học viên còn hạn (nguồn: " . implode(', ', $nguon) . ')');

            return;
        }

        $truoc = $lop->members()->count();

        // `syncWithoutDetaching`: chạy lại lệnh không được gỡ ai ra khỏi lớp.
        $lop->members()->syncWithoutDetaching(
            $ungVien->mapWithKeys(fn ($id) => [$id => ['added_at' => now()]])->all()
        );

        $sau = $lop->members()->count();
        $this->line("      thành viên: {$truoc} → {$sau} (nguồn: " . implode(', ', $nguon) . ')');
    }

    /** @param array<string, ClassGroup> $lop */
    private function dungBuoi(array $dinhNghia, array $lop, bool $thu): void
    {
        $this->line('<comment>━━━ BUỔI HỌC (lớp online) ━━━</comment>');

        foreach ($dinhNghia as $s) {
            $ten = $s['title'] ?? null;
            $thuTrongTuan = (int) ($s['weekday'] ?? 0);

            if (! $ten || $thuTrongTuan < 1 || $thuTrongTuan > 7) {
                $this->line('  <error>✗</error> Bỏ qua một buổi thiếu "title" hoặc "weekday" (1–7).');

                continue;
            }

            if (ClassSession::where('title', $ten)->where('repeat_weekly', true)->exists()) {
                $this->line("  = {$ten} (đã có lịch lặp, giữ nguyên)");

                continue;
            }

            $batDau = $this->lanToiCua($thuTrongTuan, (string) ($s['start'] ?? '19:30'));
            $ketThuc = isset($s['end'])
                ? $this->cungNgayVoi($batDau, (string) $s['end'])
                : null;

            $tenLop = $s['group'] ?? null;
            $lopCuaBuoi = $tenLop ? ($lop[$tenLop] ?? null) : null;

            if ($tenLop && ! $lopCuaBuoi) {
                $this->line("  <error>✗</error> {$ten}: không tìm thấy lớp \"{$tenLop}\" — bỏ qua"
                    . ' (gắn nhầm thành buổi mở cho toàn trường là lộ quyền, nên thà không tạo).');

                continue;
            }

            $moTa = self::THU[$thuTrongTuan] . ' ' . $batDau->format('H:i')
                . ($ketThuc ? '–' . $ketThuc->format('H:i') : '')
                . ', bắt đầu ' . $batDau->format('d/m/Y')
                . ' · lớp: ' . ($lopCuaBuoi?->name ?? 'KHÔNG GẮN LỚP');

            if ($thu) {
                $this->line("  + {$ten} — {$moTa} (chạy thử)");

                continue;
            }

            $buoi = ClassSession::create([
                'title'          => $ten,
                'description'    => $s['description'] ?? null,
                'class_group_id' => $lopCuaBuoi?->id,
                'meet_link'      => MeetLink::normalize($s['meet_link'] ?? null) ?: null,
                'starts_at'      => $batDau,
                'ends_at'        => $ketThuc,
                'is_active'      => true,
                'repeat_weekly'  => true,
            ]);

            $this->line("  <info>+</info> {$ten} → buổi #{$buoi->id} — {$moTa}");
        }
    }

    /**
     * Lần tới của một thứ trong tuần, tính theo ĐỒNG HỒ SERVER (giờ Việt Nam).
     *
     * Hôm nay đúng thứ đó mà chưa tới giờ thì lấy luôn hôm nay — bỏ qua một buổi
     * chỉ vì lệnh chạy lúc chiều là mất một buổi dạy thật.
     */
    private function lanToiCua(int $thuTrongTuan, string $gio): Carbon
    {
        [$h, $p] = array_pad(explode(':', $gio), 2, '0');

        $moc = now()->startOfWeek(Carbon::MONDAY)
            ->addDays($thuTrongTuan - 1)
            ->setTime((int) $h, (int) $p);

        return $moc->isPast() ? $moc->addWeek() : $moc;
    }

    private function cungNgayVoi(Carbon $batDau, string $gio): Carbon
    {
        [$h, $p] = array_pad(explode(':', $gio), 2, '0');

        return $batDau->copy()->setTime((int) $h, (int) $p);
    }
}
