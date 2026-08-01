<?php

namespace App\Console\Commands;

use App\Models\AttemptAnswer;
use App\Support\SpeakingAudio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Dọn file ghi âm bài Nói đã cũ (rủi ro 3 trong PLAN_CHAM_SPEAKING_AI.md:
 * hosting 30GB chia cho 21 web, audio tích luỹ mãi).
 *
 * ⚠️ XOÁ DỮ LIỆU THẬT, KHÔNG KHÔI PHỤC ĐƯỢC. Ba lớp chắn:
 *   1. Chỉ đụng file cũ hơn `--days` (mặc định 180 ngày).
 *   2. KHÔNG xoá bản ghi của phần chưa chấm xong — xoá đi thì cả AI lẫn giáo
 *      viên đều không còn gì để nghe.
 *   3. `--dry-run` để xem trước. CHẠY THỬ TRƯỚC KHI BẬT CRON.
 *
 * Bản ghi trong DB được giữ nguyên; chỉ file bị xoá. Giao diện sẽ hiện
 * "Không tìm thấy bản ghi âm" thay vì vỡ trang.
 */
class CleanupSpeakingAudio extends Command
{
    protected $signature = 'speaking:cleanup-audio
                            {--days=180 : Giữ lại file mới hơn số ngày này}
                            {--dry-run : Chỉ liệt kê, không xoá}';

    protected $description = 'Xoá file ghi âm bài Nói đã cũ và đã chấm xong, để giải phóng ổ đĩa';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');

        if ($days < 30) {
            $this->error("Từ chối chạy với --days={$days}. Dưới 30 ngày quá rủi ro cho bài của học viên.");
            return self::FAILURE;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists('speaking_attempts')) {
            $this->info('Chưa có thư mục speaking_attempts, không có gì để dọn.');
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        // Tập file PHẢI GIỮ: thuộc phần chưa chấm xong, hoặc thuộc bài còn mới.
        // Dựng từ DB một lần rồi đối chiếu, thay vì truy vấn theo từng file.
        $protected = $this->protectedPaths($cutoff);

        $this->info('Ngưỡng: xoá file cũ hơn ' . $cutoff->format('d/m/Y') . " ({$days} ngày)");
        $this->info('Số bản ghi được bảo vệ: ' . count($protected));

        $deleted = 0;
        $freedBytes = 0;
        $failed = 0;

        foreach ($disk->files('speaking_attempts') as $path) {
            if (isset($protected[$path])) {
                continue;
            }

            try {
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
                Log::warning("speaking:cleanup-audio: bỏ qua {$path}: " . $e->getMessage());
            }
        }

        $summary = ($dryRun ? '[THỬ] ' : '')
            . "Đã dọn {$deleted} file, giải phóng " . round($freedBytes / 1048576, 1) . ' MB'
            . ($failed ? ", {$failed} file lỗi (xem log)" : '');

        $this->info($summary);

        if (!$dryRun && $deleted > 0) {
            Log::info('speaking:cleanup-audio: ' . $summary);
        }

        return self::SUCCESS;
    }

    /**
     * Đường dẫn không được xoá, dạng [path => true] để tra O(1).
     *
     * Bảo vệ hai nhóm: phần GIÁO VIÊN chưa chấm (còn phải nghe), và phần thuộc
     * bài mới hơn ngưỡng. Trạng thái AI đã chấm KHÔNG đủ để xoá — học viên vẫn
     * có quyền nghe lại bài của mình trong thời gian còn hạn.
     */
    protected function protectedPaths(\Illuminate\Support\Carbon $cutoff): array
    {
        $protected = [];

        AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->where('skill', 'speaking'))
            ->where(function ($q) use ($cutoff) {
                $q->whereNotIn('grading_status', ['graded', 'manually_graded'])
                  ->orWhere('updated_at', '>=', $cutoff);
            })
            ->select(['id', 'answer'])
            ->chunkById(500, function ($answers) use (&$protected) {
                foreach ($answers as $answer) {
                    foreach (SpeakingAudio::pathsOf($answer->answer) as $path) {
                        $protected[$path] = true;
                    }
                }
            });

        return $protected;
    }
}
