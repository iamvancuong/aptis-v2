<?php

namespace App\Jobs;

use App\Exceptions\AiGradingException;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Services\AiService;
use App\Support\SpeakingAudio;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Chấm Nói bằng AI: audio → phiên âm → chấm transcript (cách A).
 *
 * Điểm AI là NHÁP THAM KHẢO. Giáo viên chấm tay luôn được ưu tiên và ghi đè.
 */
class ProcessSpeakingGrading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Phiên âm tối đa 120s + chấm 45s, cộng dư cho bài nhiều bản ghi. */
    public int $timeout = 300;

    /** Giãn dần giữa các lượt: lỗi 429/5xx hiếm khi hết ngay sau 1 giây. */
    public array $backoff = [30, 120];

    public function __construct(
        public readonly int $attemptAnswerId,
        public readonly array $questionData,
    ) {}

    public function handle(AiService $ai): void
    {
        $answer = AttemptAnswer::with('attempt.user')->find($this->attemptAnswerId);

        if (!$answer) {
            Log::warning("ProcessSpeakingGrading: không tìm thấy AttemptAnswer #{$this->attemptAnswerId}.");
            return;
        }

        // Giáo viên đã chấm tay thì dừng — điểm người luôn thắng điểm máy.
        // Cũng chặn trường hợp job chạy lại sau khi cô Dung đã chấm xong.
        if (in_array($answer->grading_status, ['graded', 'manually_graded', 'ai_graded'], true)) {
            Log::info("ProcessSpeakingGrading: #{$answer->id} đã ở trạng thái {$answer->grading_status}, bỏ qua.");
            return;
        }

        $part = (int) ($this->questionData['part'] ?? 0);

        try {
            $transcript = $this->resolveTranscript($ai, $answer);

            $result = $ai->gradeSpeaking([
                'part'          => $part,
                'question_stem' => $this->questionData['stem'] ?? '',
                'metadata'      => $this->questionData['metadata'] ?? [],
                'transcript'    => $transcript,
            ], $answer->attempt?->user?->target_level ?? 'B2');

            $feedback = $result['feedback'];

            $answer->update([
                'grading_status' => 'ai_graded',
                'score'          => $feedback['overall_score_10'],
                'ai_metadata'    => [
                    'transcript' => $transcript,
                    'feedback'   => $feedback,
                    'usage'      => $result['usage'] ?? null,
                    'graded_at'  => now()->toDateTimeString(),
                ],
            ]);

            $this->refreshAttemptScore($answer->attempt_id);

            Log::info("ProcessSpeakingGrading: #{$answer->id} đã chấm ({$feedback['overall_score_10']}/10).");
        } catch (AiGradingException $e) {
            if ($e->permanent) {
                // Thử lại cũng vẫn hỏng → dừng ở đây, báo ra giao diện và hoàn lượt.
                $this->markFailed($answer, $e->reason, $e->userMessage());
                Log::warning("ProcessSpeakingGrading: #{$answer->id} hỏng vĩnh viễn ({$e->reason}): {$e->getMessage()}");
                return;
            }

            // Lỗi tạm → ném lại để queue thử lượt sau.
            Log::warning("ProcessSpeakingGrading: #{$answer->id} lỗi tạm ({$e->reason}), sẽ thử lại: {$e->getMessage()}");
            throw $e;
        } catch (\Throwable $e) {
            // Lỗi ngoài dự tính: vẫn cho retry, nhưng phải log đủ để truy được.
            Log::error("ProcessSpeakingGrading: #{$answer->id} lỗi không lường trước: " . $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Lấy transcript, ưu tiên bản đã phiên âm ở lượt trước.
     *
     * Phiên âm xong mà bước chấm hỏng thì lượt retry sẽ phiên âm lại — trả tiền
     * hai lần cho cùng một file. Nên transcript được ghi xuống DB NGAY khi có,
     * trước khi gọi bước chấm.
     */
    protected function resolveTranscript(AiService $ai, AttemptAnswer $answer): string
    {
        $existing = $answer->ai_metadata['transcript'] ?? null;
        if (is_string($existing) && trim($existing) !== '') {
            Log::info("ProcessSpeakingGrading: #{$answer->id} dùng lại transcript đã có.");
            return $existing;
        }

        $paths = SpeakingAudio::pathsOf($answer->answer);

        if (empty($paths)) {
            throw AiGradingException::permanent('file_missing', "AttemptAnswer #{$answer->id} không có đường dẫn audio nào.");
        }

        $pieces = [];
        $lastError = null;

        // Một phần thi có thể gồm nhiều bản ghi. Hỏng một bản không nên bỏ cả
        // phần — chấm phần nghe được vẫn hơn là không trả gì cho học viên.
        foreach ($paths as $path) {
            try {
                $pieces[] = $ai->transcribe($path)['text'];
            } catch (AiGradingException $e) {
                $lastError = $e;
                Log::warning("ProcessSpeakingGrading: #{$answer->id} bỏ qua {$path} ({$e->reason}).");

                // Lỗi tạm (mạng/429) thì đừng âm thầm chấm thiếu — để queue thử lại cả phần.
                if (!$e->permanent) {
                    throw $e;
                }
            }
        }

        if (empty($pieces)) {
            throw $lastError ?? AiGradingException::permanent('no_speech', "AttemptAnswer #{$answer->id}: không phiên âm được bản ghi nào.");
        }

        $transcript = trim(implode("\n", $pieces));

        // Ghi ngay, trước bước chấm.
        $answer->update([
            'ai_metadata' => array_merge($answer->ai_metadata ?? [], [
                'transcript' => $transcript,
                'transcribed_at' => now()->toDateTimeString(),
            ]),
        ]);

        return $transcript;
    }

    /** Ghi trạng thái hỏng để giao diện nói rõ, thay vì treo "đang chờ" mãi. */
    protected function markFailed(AttemptAnswer $answer, string $reason, string $userMessage): void
    {
        try {
            $answer->update([
                'grading_status' => 'ai_failed',
                'ai_metadata' => array_merge($answer->ai_metadata ?? [], [
                    'error' => ['reason' => $reason, 'message' => $userMessage],
                    'failed_at' => now()->toDateTimeString(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error("ProcessSpeakingGrading: không ghi được trạng thái hỏng cho #{$answer->id}: " . $e->getMessage());
        }

        $this->refundCredit($answer);
    }

    /** Hoàn lượt đã trừ lúc nộp bài, vì học viên không nhận được kết quả nào. */
    protected function refundCredit(AttemptAnswer $answer): void
    {
        try {
            $user = $answer->attempt?->user;
            $part = (int) ($this->questionData['part'] ?? 0);

            if ($user && $part > 0) {
                $user->refundSpeakingAiUsage($part);
            }
        } catch (\Throwable $e) {
            Log::error("ProcessSpeakingGrading: hoàn lượt thất bại cho #{$answer->id}: " . $e->getMessage());
        }
    }

    /**
     * Tính lại % tổng của bài từ các phần đã có điểm.
     *
     * Mỗi phần là một job chạy song song, nên phải khoá dòng attempt: hai job
     * cùng đọc-rồi-ghi sẽ ghi đè nhau và tổng điểm ra sai.
     */
    protected function refreshAttemptScore(int $attemptId): void
    {
        try {
            DB::transaction(function () use ($attemptId) {
                $attempt = Attempt::lockForUpdate()->find($attemptId);
                if (!$attempt) {
                    return;
                }

                $scored = AttemptAnswer::where('attempt_id', $attemptId)
                    ->whereIn('grading_status', ['ai_graded', 'graded', 'manually_graded'])
                    ->pluck('score')
                    ->filter(fn ($s) => $s !== null);

                if ($scored->isEmpty()) {
                    return;
                }

                // Thang phần là 0–10, điểm bài lưu theo %.
                $attempt->update([
                    'score' => round($scored->avg() * 10, 2),
                ]);
            });
        } catch (\Throwable $e) {
            // Điểm từng phần mới là thứ học viên đọc; tổng sai không đáng để
            // huỷ cả job và chấm lại từ đầu.
            Log::error("ProcessSpeakingGrading: không cập nhật được điểm tổng attempt #{$attemptId}: " . $e->getMessage());
        }
    }

    /** Chạy khi hết cả 3 lượt. */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessSpeakingGrading: hỏng hẳn sau {$this->tries} lượt cho #{$this->attemptAnswerId}: " . $exception->getMessage());

        $answer = AttemptAnswer::with('attempt.user')->find($this->attemptAnswerId);

        if (!$answer || in_array($answer->grading_status, ['graded', 'manually_graded', 'ai_graded'], true)) {
            return;
        }

        $reason = $exception instanceof AiGradingException ? $exception->reason : 'unknown';
        $message = $exception instanceof AiGradingException
            ? $exception->userMessage()
            : 'Chấm tự động chưa hoàn tất cho phần này.';

        $this->markFailed($answer, $reason, $message);
    }
}
