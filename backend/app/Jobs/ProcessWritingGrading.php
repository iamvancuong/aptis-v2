<?php

namespace App\Jobs;

use App\Models\AttemptAnswer;
use App\Services\AiService;
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
                $aiMetadata = $aiService->gradeWriting([
                    'part'          => $this->questionData['part'],
                    'word_limit'    => $this->questionData['word_limit'] ?? null,
                    'question_stem' => $this->questionData['stem'] ?? '',
                    'student_answer' => $userAnswer,
                ]);
            }

            $attemptAnswer->update([
                'grading_status' => 'ai_graded',
                'ai_metadata'    => $aiMetadata,
            ]);

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
        // Mark as failed so admin can see it
        AttemptAnswer::where('id', $this->attemptAnswerId)
            ->where('grading_status', 'pending')
            ->update(['grading_status' => 'pending']); // keep pending for manual review
    }
}
