<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSpeakingGrading;
use App\Models\Attempt;
use App\Services\AiService;
use App\Support\SpeakingAudio;
use Illuminate\Console\Command;

/**
 * Chấm Nói bằng AI cho MỘT bài, chạy ngay tại chỗ và in ra từng bước.
 *
 * Hai công dụng:
 *   1. **Chẩn đoán** — trả lời dứt điểm "AI có chạy không" trong ~30 giây, thay
 *      vì nộp bài rồi ngồi đoán xem job có nổ hay không. Lỗi hiện thẳng ra màn
 *      hình chứ không nằm im trong `storage/logs`.
 *   2. **Cứu bài lẻ** — bài nộp trước khi bật tính năng, hoặc job chết vì mạng.
 *
 * KHÔNG phải công cụ chấm hàng loạt: nhận đúng một `attempt` mỗi lần, có chủ ý
 * (phạm vi đã chốt là không backfill 2.537 bài tồn).
 */
class GradeSpeakingAttempt extends Command
{
    protected $signature = 'speaking:grade-attempt
                            {attempt : ID của Attempt (không phải MockTest)}
                            {--force : Chấm lại cả phần đã có kết quả AI}';

    protected $description = 'Chấm AI cho một bài Nói ngay tại chỗ, in rõ từng bước (dùng để chẩn đoán)';

    public function handle(AiService $ai): int
    {
        $attempt = Attempt::with('attemptAnswers.question', 'user')->find($this->argument('attempt'));

        if (!$attempt) {
            $this->error('Không tìm thấy Attempt #' . $this->argument('attempt'));
            $this->line('Mẹo: ID trên URL /mock-test/{id}/result là MockTest, KHÔNG phải Attempt.');
            $this->line('Tra Attempt của nó: MockTest::find(id)->attempts()->first()->id');
            return self::FAILURE;
        }

        if ($attempt->skill !== 'speaking') {
            $this->error("Attempt #{$attempt->id} là bài {$attempt->skill}, không phải speaking.");
            return self::FAILURE;
        }

        $this->info("Attempt #{$attempt->id} · học viên: " . ($attempt->user->email ?? '?') . " · nộp lúc {$attempt->created_at}");
        $this->line('Chấm Nói bằng AI: ' . (config('services.openai.speaking_ai_enabled', true) ? 'ĐANG BẬT' : '⚠️ ĐANG TẮT (SPEAKING_AI_ENABLED=false)'));
        $this->line('Model phiên âm: ' . config('services.openai.transcribe_model'));
        $this->line('OPENAI_API_KEY: ' . (config('services.openai.key') ? 'có' : '⚠️ THIẾU'));
        $this->newLine();

        $done = 0;

        foreach ($attempt->attemptAnswers as $answer) {
            $part = $answer->question?->part ?? '?';
            $paths = SpeakingAudio::pathsOf($answer->answer);
            $label = "Part {$part} (answer#{$answer->id})";

            if (empty($paths)) {
                $this->line("  {$label}: BỎ QUA — không có bản ghi âm.");
                continue;
            }

            if (!empty($answer->ai_metadata['feedback']) && !$this->option('force')) {
                $this->line("  {$label}: BỎ QUA — đã có kết quả AI. Dùng --force để chấm lại.");
                continue;
            }

            if (in_array($answer->grading_status, ['graded', 'manually_graded'], true) && !$this->option('force')) {
                $this->line("  {$label}: BỎ QUA — giáo viên đã chấm.");
                continue;
            }

            $this->line("  {$label}: đang chấm… (" . count($paths) . ' bản ghi)');

            // Cho phép chấm lại: job từ chối chạy trên phần đã có kết quả.
            if ($this->option('force')) {
                $answer->update(['grading_status' => 'pending', 'ai_metadata' => null]);
            }

            try {
                (new ProcessSpeakingGrading($answer->id, [
                    'part'     => (int) ($answer->question?->part ?? 0),
                    'stem'     => $answer->question?->stem ?? '',
                    'metadata' => $answer->question?->metadata ?? [],
                ]))->handle($ai);

                $answer->refresh();

                if ($answer->grading_status === 'ai_graded') {
                    $this->info("    ✓ {$answer->score}/10 — " . mb_substr((string) ($answer->ai_metadata['transcript'] ?? ''), 0, 60) . '…');
                    $done++;
                } else {
                    $reason = $answer->ai_metadata['error']['reason'] ?? '?';
                    $message = $answer->ai_metadata['error']['message'] ?? '';
                    $this->warn("    ✗ hỏng ({$reason}): {$message}");
                }
            } catch (\Throwable $e) {
                // Lỗi TẠM được job ném ra để queue thử lại. Chạy tay thì không có
                // ai thử lại, nên in ra đây — đây chính là chỗ lộ việc host chặn
                // outbound tới api.openai.com.
                $this->error('    ✗ ' . get_class($e) . ': ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("Xong. Chấm được {$done} phần.");
        $this->line('Xem kết quả: /speaking-history/' . $attempt->id);

        return self::SUCCESS;
    }
}
