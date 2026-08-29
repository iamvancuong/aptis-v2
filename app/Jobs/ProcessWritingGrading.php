<?php

namespace App\Jobs;

use App\Models\AttemptAnswer;
use App\Services\AiService;
use App\Support\AttemptScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWritingGrading implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max attempts before marking as failed.
     */
    public int $tries = 3;

    /**
     * Timeout per attempt (seconds). OpenAI typically responds in < 15s.
     */
    public int $timeout = 60;

    public function __construct(
        public readonly int $attemptAnswerId,
        public readonly array $questionData,
    ) {}

    public function handle(AiService $aiService): void
    {
        $attemptAnswer = AttemptAnswer::find($this->attemptAnswerId);

        if (!$attemptAnswer) {
            Log::warning("ProcessWritingGrading: AttemptAnswer #{$this->attemptAnswerId} not found.");
            return;
        }

        // Skip if already graded
        if (in_array($attemptAnswer->grading_status, ['ai_graded', 'manually_graded'])) {
            return;
        }

        $userAnswer = $attemptAnswer->answer;

        try {
            if (empty($userAnswer)) {
                $aiMetadata = [
                    'feedback' => [
                        'schema_version' => 3,
                        'part' => $this->questionData['part'],
                        'overall_score' => 0,
                        'scores' => ['grammar' => 0, 'vocabulary' => 0, 'coherence' => 0, 'task_fulfillment' => 0],
                        'feedback' => ['task_fulfillment' => 'No answer provided.'],
                    ]
                ];
            } else {
                $targetLevel = $attemptAnswer->attempt->user->target_level ?? 'B2';
                $aiMetadata = $aiService->gradeWriting([
                    'part'          => $this->questionData['part'],
                    'word_limit'    => $this->questionData['word_limit'] ?? null,
                    'question_stem' => $this->questionData['stem'] ?? '',
                    'metadata'      => $this->questionData['metadata'] ?? [],
                    'student_answer' => $userAnswer,
                ], $targetLevel);

                // Enforce Part 1 word limit server-side since AI often ignores prompt constraints
                $aiMetadata = $this->enforcePartLimits($aiMetadata, $this->questionData['part']);
            }

            // Điểm phải được GHI, không chỉ nằm trong `ai_metadata`.
            //
            // Bản cũ chỉ lưu `grading_status` + `ai_metadata`, bỏ trống cột
            // `score`. Bài Viết vì thế được AI chấm xong xuôi, nhận xét hiện đủ,
            // mà mọi con số trên trang kết quả vẫn là 0 — cả điểm từng phần
            // ("0.0/10") lẫn vòng tròn điểm tổng ("0%"). Học viên đọc thành "làm
            // rồi mà không được điểm gì". Bên Nói vốn đã ghi đúng; đây là chỗ
            // hai kỹ năng lệch nhau.
            //
            // Điểm phần tính TỪ 4 TIÊU CHÍ, không lấy `overall_score` của AI —
            // xem lý do trong docblock của AttemptScore (con số đó không được
            // kiểm chứng và đã sai sẵn trong bản mock).
            $attemptAnswer->update([
                'grading_status' => 'ai_graded',
                'score'          => AttemptScore::writingScoreOutOfTen($aiMetadata['feedback'] ?? []),
                'ai_metadata'    => $aiMetadata,
            ]);

            AttemptScore::refresh($attemptAnswer->attempt_id);

            Log::info("ProcessWritingGrading: AttemptAnswer #{$this->attemptAnswerId} graded successfully.");
        } catch (\Exception $e) {
            Log::error("ProcessWritingGrading: Failed for #{$this->attemptAnswerId}: " . $e->getMessage());
            // Re-throw so queue retries
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessWritingGrading: Permanently failed for #{$this->attemptAnswerId}: " . $exception->getMessage());
        AttemptAnswer::where('id', $this->attemptAnswerId)
            ->where('grading_status', 'pending')
            ->update(['grading_status' => 'pending']); // keep pending for manual review
    }

    /**
     * Post-process AI metadata to enforce word limits per part.
     * This is a safety net for when the AI ignores prompt constraints.
     */
    private function enforcePartLimits(array $aiMetadata, int $part): array
    {
        $feedback = $aiMetadata['feedback'] ?? null;
        if (!$feedback || empty($feedback['part_responses'])) {
            return $aiMetadata;
        }

        foreach ($feedback['part_responses'] as &$response) {
            $sample = $response['improved_sample'] ?? '';
            if (empty($sample)) continue;

            if ($part === 1) {
                // Hard limit: max 5 words for Part 1
                $words = explode(' ', trim($sample));
                if (count($words) > 5) {
                    $response['improved_sample'] = implode(' ', array_slice($words, 0, 5));
                }
            }

            if ($part === 2) {
                // Soft limit: warn if over 30 words, but don't aggressively truncate
                $wordCount = str_word_count($sample);
                if ($wordCount > 35) {
                    $words = explode(' ', trim($sample));
                    $response['improved_sample'] = implode(' ', array_slice($words, 0, 30));
                }
            }
        }

        $aiMetadata['feedback']['part_responses'] = $feedback['part_responses'];
        return $aiMetadata;
    }
}

