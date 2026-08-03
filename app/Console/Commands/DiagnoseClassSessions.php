<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * "Tạo buổi rồi mà học viên không vào được" — lệnh này in ra ĐÚNG cửa nào đang chặn.
 *
 * Có 3 tầng điều kiện độc lập (buổi hiển thị · buổi mở cửa · học viên có tư cách),
 * mỗi tầng lại vài mệnh đề. Nhìn giao diện chỉ thấy "không có nút" nên không đoán
 * được tầng nào hỏng. Cùng lý do đã viết `speaking:grade-attempt` ở §27.
 */
class DiagnoseClassSessions extends Command
{
    protected $signature = 'classes:diagnose
                            {session? : ID buổi học. Bỏ trống = kiểm mọi buổi}
                            {--user= : Email học viên muốn kiểm tư cách}';

    protected $description = 'Chẩn đoán vì sao học viên không vào được buổi học';

    public function handle(): int
    {
        $sessions = $this->argument('session')
            ? ClassSession::with('classGroup')->whereKey($this->argument('session'))->get()
            : ClassSession::with('classGroup')->orderByDesc('id')->get();

        if ($sessions->isEmpty()) {
            $this->error('Không tìm thấy buổi học nào.');
            return self::FAILURE;
        }

        $hocVien = null;
        if ($email = $this->option('user')) {
            $hocVien = User::firstWhere('email', $email);

            if (! $hocVien) {
                $this->error("Không có tài khoản nào với email: {$email}");
                return self::FAILURE;
            }
        }

        $this->line('Giờ hệ thống hiện tại: <info>' . now()->format('H:i:s d/m/Y') . '</info> ('
            . config('app.timezone') . ')');
        $this->newLine();

        foreach ($sessions as $s) {
            $this->chanDoanBuoi($s, $hocVien);
        }

        if (! $hocVien) {
            $this->newLine();
            $this->line('💡 Thêm <info>--user=email@hocvien</info> để kiểm cả tư cách thành viên.');
        }

        return self::SUCCESS;
    }

    private function chanDoanBuoi(ClassSession $s, ?User $hocVien): void
    {
        $this->line("<comment>━━━ Buổi #{$s->id} — {$s->title} ━━━</comment>");

        // ── Tầng 1: buổi có nằm trong danh sách học viên nhìn thấy không
        $this->ket('Đang bật (is_active)', $s->is_active,
            'Buổi đang TẮT → không ai thấy. Bật ở /admin/class-sessions.');

        $this->ket('Chưa kết thúc', ! $s->hasEnded(),
            'Đã qua giờ kết thúc (' . $s->ends_at?->format('H:i d/m/Y') . ') → buổi tự ẩn.');

        // ── Tầng 2: buổi có mở cửa không
        $gioMo = $s->joinOpensAt();
        $this->ket('Đã tới giờ mở cửa', ! $s->isUpcoming(),
            'Cửa lớp mở lúc ' . $gioMo?->format('H:i d/m/Y')
            . ' (trước giờ bắt đầu ' . ClassSession::JOIN_EARLY_MINUTES . ' phút). Chưa tới.');

        $link = $s->effectiveMeetLink();
        $nguonLink = $s->meet_link ? 'của buổi' : ($s->classGroup?->meet_link ? 'kế thừa từ lớp' : '—');
        $this->ket('Có link phòng', (bool) $link,
            'CHƯA CÓ LINK ở cả buổi lẫn lớp → nút "Vào lớp" không hiện.'
            . ' Dán link ở buổi, hoặc ở lớp để mọi buổi dùng chung.');

        if ($link) {
            $this->line("    link: {$link} ({$nguonLink})");
        }

        // ── Tầng 3: ai được vào
        if ($s->class_group_id === null) {
            $this->line('  <info>✅</info> Phạm vi: MỌI học viên còn hạn (buổi không gắn lớp)');
        } else {
            $lop = $s->classGroup;
            $soTV = $lop?->members()->count() ?? 0;

            $this->ket('Lớp đang bật', (bool) $lop?->is_active,
                "Lớp \"{$lop?->name}\" đang TẮT → đóng mọi buổi của lớp, kể cả khách mời.");

            $this->ket("Lớp \"{$lop?->name}\" có thành viên", $soTV > 0,
                'Lớp CHƯA CÓ THÀNH VIÊN NÀO → không ai vào được.'
                . " Thêm ở /admin/class-groups/{$s->class_group_id}/thanh-vien");

            if ($soTV > 0) {
                $this->line("    {$soTV} thành viên · {$s->extraMembers()->count()} khách mời riêng");
            }
        }

        $this->line($s->isJoinable()
            ? '  <info>➜ isJoinable() = TRUE</info> — buổi đã mở cửa.'
            : '  <error>➜ isJoinable() = FALSE</error> — xem dòng ❌ ở trên.');

        if ($hocVien) {
            $this->chanDoanHocVien($s, $hocVien);
        }

        $this->newLine();
    }

    private function chanDoanHocVien(ClassSession $s, User $u): void
    {
        $this->line("  <comment>· Học viên: {$u->name} <{$u->email}></comment>");

        $this->ket('Tài khoản còn hạn', ! $u->isExpired(),
            'Hết hạn ' . $u->expires_at?->format('d/m/Y') . ' → bị logout ở mọi trang.', 4);

        $this->ket('Không bị khoá', ! $u->isBlocked(), 'Tài khoản đang bị khoá.', 4);

        if ($s->class_group_id !== null) {
            $laTV    = $u->classGroups()->whereKey($s->class_group_id)->exists();
            $laKhach = $u->classSessionInvites()->whereKey($s->getKey())->exists();

            $this->ket('Thuộc lớp hoặc được mời riêng', $laTV || $laKhach,
                "KHÔNG thuộc lớp \"{$s->classGroup?->name}\" và cũng không phải khách mời của buổi này."
                . ' Đây là lý do bị chặn ở cổng vào.', 4);
        }

        $vao = $u->canJoinClassSession($s) && $s->isJoinable();

        $this->line($vao
            ? '    <info>➜ VÀO ĐƯỢC.</info> Nếu thực tế vẫn không thấy nút thì xoá cache view: php artisan view:cache'
            : '    <error>➜ KHÔNG VÀO ĐƯỢC.</error> Sửa các dòng ❌ ở trên.');
    }

    private function ket(string $nhan, bool $dat, string $viSao, int $thut = 2): void
    {
        $le = str_repeat(' ', $thut);

        if ($dat) {
            $this->line("{$le}<info>✅</info> {$nhan}");
            return;
        }

        $this->line("{$le}<error>❌</error> {$nhan}");
        $this->line("{$le}   → {$viSao}");
    }
}
