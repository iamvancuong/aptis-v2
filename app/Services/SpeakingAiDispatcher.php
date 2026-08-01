<?php

namespace App\Services;

use App\Jobs\ProcessSpeakingGrading;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Đẩy job chấm Nói bằng AI sau khi học viên nộp bài.
 *
 * Dùng chung cho MockTestController và PracticeController để luật trừ lượt chỉ
 * nằm ở một chỗ — bản Writing đang chép logic này ở hai nơi và hai bản đã lệch
 * nhau (một bên so `$credits > 0`, bên kia so `!== 'unlimited' && <= 0`).
 *
 * NGUYÊN TẮC: hàm này KHÔNG BAO GIỜ được ném lỗi ra ngoài. Nó chạy trong cùng
 * transaction với việc lưu bài làm — hàng đợi trục trặc mà làm rollback bài nộp
 * thì học viên mất trắng công thu âm, tệ hơn nhiều so với việc không được chấm.
 */
class SpeakingAiDispatcher
{
    public function dispatchFor(Attempt $attempt, ?User $user): void
    {
        try {
            if (!$user) {
                return;
            }

            if (!config('services.openai.speaking_ai_enabled', true)) {
                Log::info('SpeakingAiDispatcher: đang tắt bằng SPEAKING_AI_ENABLED, bỏ qua.');
                return;
            }

            $attempt->load(['attemptAnswers.question']);
            $remaining = $user->getRemainingSpeakingAiCredits();

            foreach ($attempt->attemptAnswers as $answer) {
                if ($answer->grading_status !== 'pending' || !$answer->question) {
                    continue;
                }

                // Không có bản ghi nào thì không gọi AI và cũng KHÔNG trừ lượt.
                // Trừ lượt cho một phần trống là lấy không của học viên.
                if (empty($answer->answer)) {
                    continue;
                }

                if ($remaining <= 0) {
                    $answer->update(['grading_status' => 'limit_reached']);
                    continue;
                }

                $part = (int) $answer->question->part;

                ProcessSpeakingGrading::dispatch($answer->id, [
                    'part'     => $part,
                    'stem'     => $answer->question->stem,
                    'metadata' => $answer->question->metadata ?? [],
                ])->afterCommit();

                $user->recordSpeakingAiUsage($part);
                $remaining--;
            }
        } catch (\Throwable $e) {
            Log::error('SpeakingAiDispatcher: không đẩy được job chấm Nói: ' . $e->getMessage(), [
                'attempt_id' => $attempt->id ?? null,
                'exception'  => get_class($e),
            ]);
            // Nuốt lỗi có chủ đích: bài làm đã lưu là thứ phải giữ bằng mọi giá.
            // Bài không được chấm sẽ nằm ở trạng thái 'pending' — giáo viên vẫn
            // thấy trong danh sách chờ chấm, không mất đi đâu.
        }
    }
}
