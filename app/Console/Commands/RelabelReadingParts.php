<?php

namespace App\Console\Commands;

use App\Models\Quiz;
use App\Models\Set;
use App\Support\PartLabel;
use Illuminate\Console\Command;

/**
 * Đồng bộ số Part nhúng trong TIÊU ĐỀ của quiz/set Reading với nhãn đang hiển thị.
 *
 * Nhãn do code sinh ra đã đổi theo đề APTIS thật (`PartLabel`), nhưng tiêu đề là
 * dữ liệu admin nhập nên vẫn giữ số cũ — kết quả là trang chọn Part hiện
 * "Part 2-3" ở tiêu đề mà "Reading Part 2: ..." ở dòng mô tả ngay dưới.
 *
 * Lấy `PartLabel` làm nguồn duy nhất, KHÔNG chép lại bảng ánh xạ ở đây — hai bảng
 * ở hai nơi thì sớm muộn cũng lệch.
 *
 * Chỉ đổi CON SỐ, giữ nguyên phần chữ mô tả ("Multiple Choice"...) vì đó là nội
 * dung học thuật, không phải việc của lệnh này.
 *
 * Chạy lại nhiều lần vô hại: "Part 2-3" thay bằng "Part 2-3" là không đổi gì.
 */
class RelabelReadingParts extends Command
{
    protected $signature = 'reading:relabel-parts {--dry-run : Chỉ xem trước, không ghi}';

    protected $description = 'Đồng bộ số Part trong tiêu đề quiz/set Reading với nhãn hiển thị';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        $this->info($dryRun ? '[XEM TRƯỚC — không ghi gì]' : 'Đang cập nhật tiêu đề…');
        $this->newLine();

        foreach (Quiz::where('skill', 'reading')->orderBy('part')->get() as $quiz) {
            $changed += $this->sync($quiz, (int) $quiz->part, "quiz#{$quiz->id}", $dryRun);
        }

        foreach (Set::whereHas('quiz', fn ($q) => $q->where('skill', 'reading'))->with('quiz')->get() as $set) {
            if (!$set->quiz) {
                continue;
            }
            $changed += $this->sync($set, (int) $set->quiz->part, "set#{$set->id}", $dryRun);
        }

        $this->newLine();

        if ($changed === 0) {
            $this->info('Không có gì phải đổi — tiêu đề đã khớp nhãn.');
            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "Sẽ đổi {$changed} dòng. Bỏ --dry-run để ghi thật."
            : "Đã đổi {$changed} dòng.");

        return self::SUCCESS;
    }

    /** @param Quiz|Set $model */
    private function sync($model, int $internalPart, string $label, bool $dryRun): int
    {
        $title = (string) $model->title;

        // Bắt cả dạng đã đổi rồi ("Part 2-3") để chạy lần hai không nhân đôi hậu tố.
        $new = preg_replace(
            '/\bPart\s*\d+(?:-\d+)?/i',
            'Part ' . PartLabel::number('reading', $internalPart),
            $title,
            1
        );

        if ($new === null || $new === $title) {
            return 0;
        }

        $this->line("  {$label}: [{$title}]");
        $this->line("       -> [{$new}]");

        if (!$dryRun) {
            $model->update(['title' => $new]);
        }

        return 1;
    }
}
