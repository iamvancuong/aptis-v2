<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SpeakingAiUsage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Bật hạn mức AI chấm Nói tính theo BÀI. Chạy MỘT LẦN khi deploy.
 *
 * Vì sao phải nâng `speaking_ai_reset_version`: dữ liệu cũ đếm theo PHẦN, một bài
 * 4 phần thành 4 dòng. Giữ nguyên rồi áp hạn mức 10 BÀI thì học viên từng nộp 3
 * bài sẽ hiện ra là đã dùng 12 lượt — hết sạch, dù thực tế mới có 3 bài. Nâng
 * version làm mọi dòng cũ rơi về version trước nên không còn được tính, mà vẫn
 * giữ lại để tra cứu (không xoá gì).
 */
class ApplySpeakingAiLimit extends Command
{
    protected $signature = 'speaking:apply-ai-limit
                            {--limit=10 : Số BÀI Nói mỗi học viên được AI chấm}
                            {--dry-run  : Chỉ in ra sẽ đổi gì, không ghi DB}';

    protected $description = 'Đặt hạn mức AI chấm Nói (tính theo bài) và cho dữ liệu đếm cũ hết hiệu lực';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) $this->option('limit');

        if ($limit < 1) {
            $this->error('--limit phải >= 1.');
            return self::FAILURE;
        }

        $hienTai = Setting::where('key', 'speaking_ai_limit')->value('value');
        $dongCu  = SpeakingAiUsage::whereNull('attempt_id')->count();
        $soUser  = User::where('role', '!=', 'admin')->count();

        $this->info($dryRun ? '🔎 CHẠY THỬ — không ghi gì vào DB' : '✍️  GHI THẬT vào DB');
        $this->newLine();

        $this->table(['Việc', 'Giá trị'], [
            ['Hạn mức hiện tại', $hienTai ?? '(chưa đặt — mặc định ' . User::SPEAKING_AI_LIMIT_MAC_DINH . ')'],
            ['Hạn mức sẽ đặt', $limit . ' BÀI / học viên'],
            ['Dòng đếm KIỂU CŨ (theo phần)', $dongCu],
            ['Học viên được nâng reset_version', $soUser],
        ]);

        if ($dongCu > 0) {
            $this->warn("⚠️  {$dongCu} dòng đếm kiểu cũ (theo phần). Một bài 4 phần thành 4 dòng, nên");
            $this->warn('    giữ nguyên là học viên mất oan lượt. Nâng reset_version cho chúng hết hiệu lực.');
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Chưa ghi gì. Bỏ --dry-run để ghi thật.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($limit) {
            Setting::updateOrCreate(
                ['key' => 'speaking_ai_limit'],
                ['value' => $limit, 'label' => 'Số BÀI Nói được AI chấm cho mỗi học viên']
            );

            // Dòng cũ KHÔNG xoá — chỉ cho hết hiệu lực bằng cách đẩy version lên.
            User::where('role', '!=', 'admin')->increment('speaking_ai_reset_version');
        });

        $this->newLine();
        $this->info("✅ Xong. Mỗi học viên có {$limit} bài Nói được AI chấm.");
        $this->line('   Một bài = 1 lượt, dù bài đó có 4 phần ghi âm.');
        $this->line('   Đổi con số sau này: /admin/settings, ô "Số BÀI Nói được AI chấm".');

        return self::SUCCESS;
    }
}
