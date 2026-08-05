<?php

namespace App\Console\Commands;

use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Cập nhật thành viên các lớp "tự gom theo ngày thi" ("Nhóm thi tuần này").
 *
 * Ô "Ngày thi (Exam Date)" ở form tạo user ghi vào `users.expires_at`, nên câu
 * "ai thi trong N ngày tới" trả lời được ngay bằng dữ liệu đang có — không phải
 * thu thập thêm gì, cũng không phải chọn tay lại mỗi tuần.
 *
 * Chạy hằng ngày. Người vừa qua ngày thi tự rơi khỏi lớp hôm sau.
 */
class SyncExamClassGroups extends Command
{
    protected $signature = 'classes:sync-exam-groups
                            {--dry-run : Chỉ in ra, không đổi thành viên lớp nào}';

    protected $description = 'Cập nhật thành viên lớp tự gom theo ngày thi sắp tới';

    public function handle(): int
    {
        $thu = (bool) $this->option('dry-run');

        $lop = ClassGroup::whereNotNull('auto_exam_days')
            ->where('auto_exam_days', '>', 0)
            ->orderBy('name')
            ->get();

        if ($lop->isEmpty()) {
            $this->line('Chưa có lớp nào bật "tự gom theo ngày thi".');

            return self::SUCCESS;
        }

        $this->line('Hôm nay: ' . now()->format('d/m/Y') . ($thu ? ' — CHẠY THỬ, không đổi gì' : ''));
        $this->newLine();

        foreach ($lop as $l) {
            $this->dongBo($l, $thu);
        }

        $this->newLine();
        $this->line('⚠️ Google KHÔNG tự cập nhật theo. Vào /admin/class-groups/{id}/thanh-vien'
            . ' copy lại danh sách mời rồi dán vào ô Khách mời của sự kiện Calendar,'
            . ' nếu không người mới vẫn phải xin duyệt và người cũ vẫn vào thẳng được.');

        return self::SUCCESS;
    }

    private function dongBo(ClassGroup $lop, bool $thu): void
    {
        $this->line("<comment>━━━ {$lop->name}</comment> (thi trong {$lop->auto_exam_days} ngày tới)");

        $canCo = User::sapThi($lop->auto_exam_days)->pluck('name', 'id');
        $dangCo = $lop->members()->pluck('users.name', 'users.id');

        $them = $canCo->diffKeys($dangCo);
        $go = $dangCo->diffKeys($canCo);

        if ($them->isEmpty() && $go->isEmpty()) {
            $this->line("    (đủ rồi — {$dangCo->count()} thành viên, không đổi)");

            return;
        }

        foreach ($them as $ten) {
            $this->line("    <info>+</info> {$ten}");
        }

        foreach ($go as $ten) {
            $this->line("    <error>-</error> {$ten} (đã qua ngày thi hoặc hết hạn)");
        }

        if ($thu) {
            $this->line("    → sẽ thêm {$them->count()}, gỡ {$go->count()} (chạy thử)");

            return;
        }

        // `sync` chứ không phải `syncWithoutDetaching`: lớp này do MÁY quản danh
        // sách. Chỉ thêm mà không gỡ thì người đã thi xong ở lại vĩnh viễn và lớp
        // phình lên mãi — đúng thứ tính năng này sinh ra để tránh.
        $lop->members()->sync(
            $canCo->keys()->mapWithKeys(fn ($id) => [$id => ['added_at' => now()]])->all()
        );

        $this->line("    → đã thêm {$them->count()}, gỡ {$go->count()} · còn {$canCo->count()} thành viên");
    }
}
