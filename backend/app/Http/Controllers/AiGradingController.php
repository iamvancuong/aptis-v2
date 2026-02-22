<?php

namespace App\Http\Controllers;

use App\Models\AttemptAnswer;
use App\Models\WritingAiUsage;
use App\Models\WritingReview;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiGradingController extends Controller
{
    public function __construct(
        protected AiService $aiService
    ) {}

    /**
     * Grade a writing attempt answer using AI.
     */
    public function grade(Request $request, AttemptAnswer $attemptAnswer)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $question = $attemptAnswer->question;
        if ($question->skill !== 'writing') {
            return response()->json(['message' => 'Only writing questions can be graded by AI.'], 400);
        }

        $part = $question->part;

        // STEP 1: Pre-check (Short Transaction)
        $canProceed = DB::transaction(function () use ($user, $part) {
            if ($user->isAdmin()) {
                return true;
            }

            $usage = WritingAiUsage::firstOrCreate(
                ['user_id' => $user->id, 'writing_part' => $part],
                ['usage_count' => 0, 'reset_version' => $user->ai_reset_version]
            );

            // Row-level lock for safety
            $usage = WritingAiUsage::where('id', $usage->id)->lockForUpdate()->first();

            // Handle Reset Logic
            if ($usage->reset_version < $user->ai_reset_version) {
                $usage->usage_count = 0;
                $usage->reset_version = $user->ai_reset_version;
                $usage->save();
            }

            if ($usage->usage_count >= 10) {
                return false;
            }

            return true;
        });

        if (!$canProceed) {
            return response()->json([
                'message' => 'Bạn đã hết lượt sử dụng AI cho phần này (Tối đa 10 lượt).',
                'limit_reached' => true
            ], 403);
        }

        // STEP 2: Call OpenAI (Outside Transaction)
        try {
            $aiResult = $this->aiService->gradeWriting([
                'part' => $part,
                'word_limit' => $question->metadata['word_limit'] ?? null,
                'question_stem' => $question->stem,
                'student_answer' => $attemptAnswer->answer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi kết nối với AI: ' . $e->getMessage()
            ], 500);
        }

        // STEP 3: Finalize (Second Short Transaction)
        $review = DB::transaction(function () use ($user, $attemptAnswer, $part, $aiResult) {
            if (!$user->isAdmin()) {
                $usage = WritingAiUsage::where('user_id', $user->id)
                    ->where('writing_part', $part)
                    ->lockForUpdate()
                    ->first();

                // Double check for race conditions
                if ($usage->usage_count >= 10) {
                    throw new \Exception('Limit reached during processing.');
                }

                $usage->increment('usage_count');
            }

            // Save raw feedback into WritingReview
            $review = WritingReview::updateOrCreate(
                ['attempt_answer_id' => $attemptAnswer->id],
                [
                    'reviewer_id' => null, // AI graded
                    'scores' => [
                        'score_estimate' => $aiResult['feedback']['overall_score_estimate'],
                        'grammar' => $aiResult['feedback']['grammar_feedback'],
                        'vocabulary' => $aiResult['feedback']['vocabulary_feedback'],
                        'coherence' => $aiResult['feedback']['coherence_feedback'],
                        'task_fulfillment' => $aiResult['feedback']['task_fulfillment_feedback'],
                    ],
                    'total_score' => 0, // AI estimate, not official score
                    'comment' => $aiResult['feedback']['improved_sample_paragraph'],
                ]
            );

            // Log metadata
            $attemptAnswer->update([
                'ai_metadata' => $aiResult['usage']
            ]);

            return $review;
        });

        return response()->json([
            'success' => true,
            'review' => $review,
            'remaining_attempts' => $user->isAdmin() ? 'unlimited' : max(0, 10 - ($user->writingAiUsages()->where('writing_part', $part)->first()?->usage_count ?? 0))
        ]);
    }

    /**
     * Get usage status for the current user.
     */
    public function getUsageStatus(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json([], 401);

        $usages = $user->writingAiUsages()
            ->where('reset_version', $user->ai_reset_version)
            ->get()
            ->keyBy('writing_part');

        $status = [];
        for ($i = 1; $i <= 4; $i++) {
            $count = $usages->has($i) ? $usages->get($i)->usage_count : 0;
            $status[$i] = [
                'count' => $count,
                'remaining' => $user->isAdmin() ? 'unlimited' : max(0, 10 - $count),
                'limit' => 10
            ];
        }

        return response()->json($status);
    }
}
