<?php

namespace App\Console\Commands;

use App\Models\AttemptAnswer;
use App\Support\SpeakingAudio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Dọn file ghi âm bài Nói MỒ CÔI — file còn nằm trên đĩa nhưng KHÔNG còn bản ghi
 * `attempt_answers` nào trỏ tới. Chủ yếu là audio của học viên đã bị xoá tài
 * khoản: khi xoá user, các dòng DB bị xoá nhưng file trên đĩa thì ở lại (trước
 * bản vá tại UserController::destroy). Đây là lệnh vét những file rác đó.
 *
 * Khác với `speaking:cleanup-audio` (xoá file CŨ của bài còn tồn tại), lệnh này
 * xoá file KHÔNG CÒN AI SỞ HỮU — không liên quan tuổi file.
 *
 * ⚠️ XOÁ DỮ LIỆU THẬT, KHÔNG KHÔI PHỤC ĐƯỢC. Hai lớp chắn:
 *   1. Nguồn sự thật là toàn bộ `attempt_answers` trong DB. Chỉ file KHÔNG nằm
 *      trong tập tham chiếu mới bị đụng tới — bài còn tồn tại luôn an toàn.
 *   2. `--min-age` (mặc định 1 ngày) chừa file vừa upload nhưng dòng answer chưa
 *      kịp ghi (đề phòng chạy trùng lúc học viên đang nộp bài).
 *   3. `--dry-run` để xem trước. CHẠY THỬ TRƯỚC KHI BẬT CRON.
 */
class CleanupOrphanSpeakingAudio extends Command
{
    protected $signature = 'speaking:cleanup-orphan-audio
                            {--min-age=1 : Chừa file mới hơn số ngày này (đề phòng bài đang nộp)}
                            {--dry-run : Chỉ liệt kê, không xoá}';

    protected $description = 'Xoá file ghi âm bài Nói mồ côi (không còn attempt_answer nào trỏ tới, ví dụ của user đã xoá)';

    public function handle(): int
    {
        $minAge = (int) $this->option('min-age');
        $dryRun = (bool) $this->option('dry-run');

        if ($minAge < 0) {
            $this->error("--min-age không được âm.");
            return self::FAILURE;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists('speaking_attempts')) {
            $this->info('Chưa có thư mục speaking_attempts, không có gì để dọn.');
            return self::SUCCESS;
        }

        // Tập file CÒN ĐƯỢC SỞ HỮU: mọi đường dẫn xuất hiện trong attempt_answers.
        // Dựng một lần thành [path => true] để đối chiếu O(1).
        $referenced = $this->referencedPaths();

        $cutoff = now()->subDays($minAge);

        $this->info('Số file được tham chiếu trong DB: ' . count($referenced));
        $this->info('Chừa file mới hơn ' . $cutoff->format('d/m/Y H:i') . " ({$minAge} ngày)");

        $deleted = 0;
        $freedBytes = 0;
        $failed = 0;

        foreach ($disk->files('speaking_attempts') as $path) {
            if (isset($referenced[$path])) {
                continue;
            }

            try {
                // File vừa upload mà answer chưa kịp ghi → chưa phải mồ côi, để yên.
                if ($disk->lastModified($path) >= $cutoff->getTimestamp()) {
                    continue;
                }

                $size = (int) $disk->size($path);

                if ($dryRun) {
                    $this->line("  [thử] sẽ xoá {$path} (" . round($size / 1024, 1) . ' KB)');
                } else {
                    $disk->delete($path);
                }

                $deleted++;
                $freedBytes += $size;
            } catch (\Throwable $e) {
                // Một file hỏng không được làm chết cả lượt dọn.
                $failed++;
                Log::warning("speaking:cleanup-orphan-audio: bỏ qua {$path}: " . $e->getMessage());
            }
        }

        $summary = ($dryRun ? '[THỬ] ' : '')
            . "Đã dọn {$deleted} file mồ côi, giải phóng " . round($freedBytes / 1048576, 1) . ' MB'
            . ($failed ? ", {$failed} file lỗi (xem log)" : '');

        $this->info($summary);

        if (!$dryRun && $deleted > 0) {
            Log::info('speaking:cleanup-orphan-audio: ' . $summary);
        }

        return self::SUCCESS;
    }

    /**
     * Mọi đường dẫn audio còn được trỏ tới, dạng [path => true] để tra O(1).
     *
     * Quét TOÀN BỘ attempt_answers (không lọc theo skill/tuổi): còn một dòng bất
     * kỳ trỏ tới file là file đó còn chủ, không được xoá.
     */
    protected function referencedPaths(): array
    {
        $referenced = [];

        AttemptAnswer::query()
            ->select(['id', 'answer'])
            ->chunkById(500, function ($answers) use (&$referenced) {
                foreach ($answers as $answer) {
                    foreach (SpeakingAudio::pathsOf($answer->answer) as $path) {
                        $referenced[$path] = true;
                    }
                }
            });

        return $referenced;
    }
}
