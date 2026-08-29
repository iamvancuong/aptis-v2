<?php

namespace App\Console\Commands;

use App\Models\AttemptAnswer;
use App\Support\AttemptScore;
use Illuminate\Console\Command;

/**
 * Tính bù điểm cho các bài Viết đã được AI chấm TRƯỚC bản vá 28/08/2026.
 *
 * Trước bản vá, `ProcessWritingGrading` chỉ lưu `grading_status` +
 * `ai_metadata` và bỏ trống cột `score`, cũng không cập nhật `attempts.score`.
 * Nhận xét của AI vẫn còn nguyên trong `ai_metadata` — chỉ thiếu con số. Nên
 * KHÔNG cần gọi lại OpenAI: tính lại từ dữ liệu đã có, không tốn một đồng nào.
 *
 * Chỉ đụng vào bài `ai_graded` chưa có điểm. Bài giáo viên chấm tay
 * (`graded` / `manually_graded`) không bao giờ bị ghi đè.
 */
class BackfillWritingScores extends Command
{
    protected $signature = 'writing:backfill-scores
                            {--dry-run : Chỉ in ra sẽ sửa gì, không ghi vào database}';

    protected $description = 'Tính bù điểm cho bài Viết đã chấm AI nhưng chưa có điểm (không gọi OpenAI)';

    public function handle(): int
    {
        $thu = (bool) $this->option('dry-run');

        if ($thu) {
            $this->warn('CHẾ ĐỘ THỬ — không ghi gì vào database.');
        }

        // `whereHas` trên attempt để chỉ lấy bài Viết: cột `skill` nằm ở attempt,
        // không nằm ở answer.
        $query = AttemptAnswer::query()
            ->where('grading_status', 'ai_graded')
            ->whereNotNull('ai_metadata')
            ->where(fn ($q) => $q->whereNull('score')->orWhere('score', 0))
            ->whereHas('attempt', fn ($q) => $q->where('skill', 'writing'));

        $tong = (clone $query)->count();

        if ($tong === 0) {
            $this->info('Không có bài Viết nào thiếu điểm. Không cần làm gì.');

            return self::SUCCESS;
        }

        $this->info("Tìm thấy {$tong} phần bài Viết đã chấm AI mà chưa có điểm.");
        $this->newLine();

        $suaDuoc  = 0;
        $khongTinh = 0;
        $attemptIds = [];

        // `chunkById` chứ không `get()`: production có hơn 20.000 lượt làm bài,
        // nạp hết một lúc là hết RAM trên shared hosting.
        $query->chunkById(200, function ($answers) use (&$suaDuoc, &$khongTinh, &$attemptIds, $thu) {
            foreach ($answers as $answer) {
                $diem = AttemptScore::writingScoreOutOfTen($answer->ai_metadata['feedback'] ?? []);

                if ($diem === null) {
                    // Không có 4 tiêu chí để tính — thường là khối lỗi cũ. Để
                    // nguyên còn hơn ghi đại một con số.
                    $khongTinh++;
                    continue;
                }

                if (! $thu) {
                    $answer->update(['score' => $diem]);
                }

                $attemptIds[$answer->attempt_id] = true;
                $suaDuoc++;
            }
        });

        // Tính lại điểm tổng MỘT LẦN cho mỗi bài, sau khi mọi phần đã có điểm —
        // làm trong vòng lặp trên thì một bài 4 phần bị tính lại 4 lần.
        if (! $thu) {
            foreach (array_keys($attemptIds) as $attemptId) {
                AttemptScore::refresh($attemptId);
            }
        }

        $this->newLine();
        $this->info("Đã tính điểm cho {$suaDuoc} phần, thuộc " . count($attemptIds) . ' bài.');

        if ($khongTinh > 0) {
            $this->warn("{$khongTinh} phần không tính được (thiếu điểm 4 tiêu chí trong ai_metadata) — để nguyên.");
        }

        if ($thu) {
            $this->newLine();
            $this->comment('Chạy lại KHÔNG kèm --dry-run để ghi thật.');
        }

        return self::SUCCESS;
    }
}
