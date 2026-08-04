<?php

namespace App\Services;

use App\Jobs\ProcessSpeakingGrading;
use App\Models\Attempt;
use App\Models\User;
use App\Support\SpeakingAudio;
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

            // ── Bước 1: xem phần nào thật sự cần chấm ──────────────────────────
            // Phải duyệt hết TRƯỚC khi trừ lượt, vì lượt tính theo BÀI: không thể
            // vừa đi vừa trừ như bản cũ (bản cũ trừ mỗi phần một lượt, nên một bài
            // 4 phần tiêu 4 lượt — hạn mức 10 thực ra chỉ được 2 bài rưỡi).
            $canCham = [];

            foreach ($attempt->attemptAnswers as $answer) {
                if (! $answer->question) {
                    continue;
                }

                // Không có bản ghi nào thì không gọi AI và cũng KHÔNG trừ lượt.
                // Trừ lượt cho một phần trống là lấy không của học viên.
                if (! SpeakingAudio::hasRecording($answer->answer)) {
                    continue;
                }

                if ($this->alreadyHandled($answer)) {
                    continue;
                }

                $canCham[] = $answer;
            }

            if (empty($canCham)) {
                return;
            }

            // ── Bước 2: kiểm lượt ──────────────────────────────────────────────
            // Bài đã có dòng lượt rồi thì cho đi tiếp mà không trừ thêm: nộp lại
            // cùng một bài, hoặc job chạy lại, không phải trả tiền lần hai.
            $daTraLuot = $user->speakingAiUsages()
                ->where('attempt_id', $attempt->id)
                ->where('reset_version', $user->speaking_ai_reset_version ?? 0)
                ->exists();

            if (! $daTraLuot && $user->getRemainingSpeakingAiCredits() <= 0) {
                foreach ($canCham as $answer) {
                    $answer->update(['grading_status' => 'limit_reached']);
                }

                return;
            }

            // ── Bước 3: TRỪ LƯỢT TRƯỚC, rồi mới đẩy job ────────────────────────
            // ⚠️ Thứ tự này quan trọng. Nếu đẩy job trước rồi mới trừ, thì với queue
            // chạy đồng bộ job có thể hỏng và HOÀN lượt ngay lúc đó — xong dòng dưới
            // mới trừ, thành ra học viên bị tính tiền cho một lần chấm hỏng. Đã dính
            // thật khi viết test. Trừ trước thì tệ nhất là hoàn lại, không mất của ai.
            $user->recordSpeakingAiUsageForAttempt($attempt->id);

            foreach ($canCham as $answer) {
                // Chuẩn hoá trạng thái trước khi đẩy job.
                //
                // `GradingService` gán 'graded' cho bài Nói ở mode practice (nghĩa là
                // "không cần chấm máy"), trùng tên với 'graded' của giáo viên chấm tay.
                // Job từ chối chạy trên 'graded' để không đè điểm người, nên nếu không
                // đưa về 'pending' ở đây thì practice sẽ KHÔNG BAO GIỜ được AI chấm.
                if ($answer->grading_status !== 'pending') {
                    $answer->update(['grading_status' => 'pending']);
                }

                ProcessSpeakingGrading::dispatch($answer->id, [
                    'part'     => (int) $answer->question->part,
                    'stem'     => $answer->question->stem,
                    'metadata' => $answer->question->metadata ?? [],
                ])->afterCommit();
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

    /**
     * Phần này đã có kết quả rồi — đừng chấm lại, đừng trừ thêm lượt.
     *
     * Không so trạng thái bằng một chuỗi cố định vì 'graded' mang hai nghĩa khác
     * nhau (giáo viên đã chấm / practice không cần chấm máy). Dấu hiệu chắc chắn
     * là ĐÃ CÓ kết quả: `ai_metadata` của máy, hoặc `feedback` của người.
     */
    protected function alreadyHandled(\App\Models\AttemptAnswer $answer): bool
    {
        if (!empty($answer->ai_metadata)) {
            return true;
        }

        if (in_array($answer->grading_status, ['ai_graded', 'manually_graded', 'limit_reached'], true)) {
            return true;
        }

        return $answer->grading_status === 'graded' && filled($answer->feedback);
    }
}
