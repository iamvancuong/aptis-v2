<?php

namespace App\Support;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tính lại điểm tổng của một bài tự luận (Nói / Viết) từ điểm các phần.
 *
 * Vì sao tách ra khỏi job: bản đầu chỉ `ProcessSpeakingGrading` có phép tính
 * này, còn `ProcessWritingGrading` KHÔNG ghi điểm gì cả — không ghi
 * `attempt_answers.score`, cũng không cập nhật `attempts.score`. Hệ quả: bài
 * Viết được AI chấm xong xuôi, nhận xét hiện đầy đủ, nhưng mọi con số điểm
 * trên trang kết quả vẫn là 0. Học viên đọc thành "làm rồi mà không được điểm".
 *
 * Gom về một chỗ để hai kỹ năng không thể lệch nhau lần nữa.
 */
class AttemptScore
{
    /**
     * Thang điểm mỗi phần mà giao diện đang hiển thị ("x/10").
     */
    public const THANG_PHAN = 10;

    /**
     * Cập nhật `attempts.score` (đơn vị %) từ trung bình điểm các phần đã chấm.
     *
     * Mỗi phần là một job chạy song song, nên phải KHOÁ dòng attempt: hai job
     * cùng đọc-rồi-ghi sẽ ghi đè nhau và tổng điểm ra sai. Từ 27/08/2026 có
     * nhiều worker chạy song song nên chuyện này càng dễ xảy ra, không còn là
     * tình huống hiếm.
     *
     * Chỉ tính trên các phần ĐÃ CÓ điểm — phần còn trong hàng đợi không kéo
     * điểm xuống, nếu không thì bài chấm dở sẽ hiện một con số thấp gây hiểu lầm
     * rồi mới nhích dần lên.
     */
    public static function refresh(int $attemptId): void
    {
        try {
            DB::transaction(function () use ($attemptId) {
                $attempt = Attempt::lockForUpdate()->find($attemptId);

                if (! $attempt) {
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
            // Điểm từng phần mới là thứ học viên đọc; tổng sai không đáng để huỷ
            // cả job và chấm lại từ đầu.
            Log::error("AttemptScore: không cập nhật được điểm tổng attempt #{$attemptId}: " . $e->getMessage());
        }
    }

    /**
     * Điểm một phần bài Viết, quy về thang 10 mà giao diện dùng.
     *
     * TỰ TÍNH TỪ 4 TIÊU CHÍ, KHÔNG TIN `overall_score` CỦA AI.
     *
     * Prompt Viết quy định `overall_score = round((grammar + vocabulary +
     * coherence + task_fulfillment) / 4)`, tức 0–5. Nhưng không có gì kiểm
     * chứng con số model trả về, và chính bản mock trong `AiService` đang trả
     * `overall_score = 14` trong khi 4 tiêu chí là 3/4/3/4 — nhân đôi số đó ra
     * 28, chặn trần thành 10/10 cho một bài trung bình. Bên Nói có
     * `normalizeSpeakingFeedback` chặn chuyện này; bên Viết thì không có gì.
     *
     * Tính lại từ 4 tiêu chí vừa loại được rủi ro đó, vừa đảm bảo con số tổng
     * luôn khớp với phần nhận xét từng tiêu chí mà học viên đọc ngay bên dưới.
     *
     * @param  array  $feedback  khối `ai_metadata['feedback']`
     */
    public static function writingScoreOutOfTen(array $feedback): ?float
    {
        $criteria = ['grammar', 'vocabulary', 'coherence', 'task_fulfillment'];
        $scores   = [];

        foreach ($criteria as $key) {
            $raw = $feedback['scores'][$key] ?? null;

            if (! is_numeric($raw)) {
                continue;
            }

            // Mỗi tiêu chí là 0–5 theo rubric; chặn hai đầu phòng model trả bậy.
            $scores[] = max(0, min(5, (float) $raw));
        }

        if ($scores === []) {
            return null;
        }

        // Trung bình thang 0–5 → nhân đôi thành 0–10.
        return round(array_sum($scores) / count($scores) * 2, 2);
    }
}
