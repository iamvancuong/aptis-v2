<?php

namespace App\Http\Controllers;

use App\Models\Set;
use App\Models\AttemptAnswer;
use App\Models\WritingAiUsage;
use App\Jobs\ProcessWritingGrading;
use App\Services\AiService;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PracticeController extends Controller
{
    public function __construct(
        private GradingService $gradingService,
        private AiService $aiService
    ) {}

    public function show(Set $set)
    {
        // Eager load questions sorted by order
        $set->load(['questions' => function ($query) {
            $query->orderBy('order');
        }, 'quiz']);

        return view('practice.show', compact('set'));
    }

    public function store(Request $request, Set $set)
    {
        $data = $request->validate([
            'answers' => 'required',
            'duration' => 'nullable|integer',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Handle Speaking single string format if answers is sent as string (e.g., from formData)
        $parsedAnswers = $request->input('answers', []);
        if (is_string($parsedAnswers)) {
            $parsedAnswers = json_decode($parsedAnswers, true) ?: [];
        }

        // --- Writing Specific Checks ---
        if ($set->quiz->skill === 'writing') {
            if ($set->deadline && now()->greaterThan($set->deadline)) {
                return response()->json(['message' => 'Hạn nộp bài đã qua. Không thể nộp bài.'], 403);
            }

            $attemptCount = \App\Models\Attempt::where('user_id', $user->id)
                ->where('set_id', $set->id)
                ->count();

            if ($attemptCount >= $set->max_attempts) {
                return response()->json(['message' => 'Bạn đã hết số lần nộp bài cho phép.'], 403);
            }
        }

        $questions = $set->questions()->orderBy('order')->get();

        // Use shared GradingService
        $result = $this->gradingService->gradeSet($questions, $parsedAnswers, 'practice');

        $totalPossiblePoints = $result['total_possible'];
        $finalScore = $result['percentage'];

        // Compute part-specific statistics for charting (correct vs total per part)
        $partStats = [];
        if ($set->quiz->skill !== 'writing') {
            foreach ($questions as $question) {
                if (!isset($partStats[$question->part])) {
                    $partStats[$question->part] = ['correct' => 0, 'total' => 0];
                }
                $partStats[$question->part]['total']++;
            }
            foreach ($result['attempt_answers'] as $ans) {
                $q = $questions->firstWhere('id', $ans['question_id']);
                if ($q && $ans['is_correct']) {
                    $partStats[$q->part]['correct']++;
                }
            }
        }

        // Create or Update Attempt
        $attempt = DB::transaction(function () use ($user, $set, $finalScore, $request, $result, $partStats) {
            $attemptId = $request->input('attempt_id');
            $attempt = null;

            if ($attemptId) {
                $attempt = \App\Models\Attempt::where('id', $attemptId)
                    ->where('user_id', $user->id)
                    ->first();
            }

            if ($attempt) {
                $attempt->update([
                    'duration_seconds' => $request->input('duration', 0),
                    'score' => $finalScore,
                    'finished_at' => now(),
                    'metadata' => ['part_stats' => $partStats],
                ]);
                // Refresh answers
                $attempt->attemptAnswers()->delete();
            } else {
                $attempt = \App\Models\Attempt::create([
                    'user_id' => $user->id,
                    'skill' => $set->quiz->skill ?? 'reading',
                    'mode' => 'practice',
                    'set_id' => $set->id,
                    'started_at' => now()->subSeconds($request->input('duration', 0)),
                    'finished_at' => now(),
                    'duration_seconds' => $request->input('duration', 0),
                    'score' => $finalScore,
                    'metadata' => ['part_stats' => $partStats],
                ]);
            }

            $attempt->attemptAnswers()->createMany($result['attempt_answers']);

            // Handle Speaking Audio Uploads
            $audioFiles = $request->file('speaking_audio');
            if ($set->quiz->skill === 'speaking' && !empty($audioFiles)) {
                Log::info('--- Speaking: Received audio files in PracticeController ---', ['count' => count($audioFiles)]);
                foreach ($audioFiles as $qId => $files) {
                    $savedPaths = [];
                    foreach ($files as $idx => $file) {
                        $path = $file->store('speaking_attempts', 'public');
                        $savedPaths[] = $path;
                        Log::info("--- Speaking: Saved audio file for Q{$qId} ---", ['path' => $path]);
                    }
                    
                    // Update the attempt_answer with the audio paths
                    $attemptAnswer = $attempt->attemptAnswers()->where('question_id', $qId)->first();
                    if ($attemptAnswer) {
                        $attemptAnswer->update([
                            'answer' => $savedPaths // Store array of paths in answer column
                        ]);
                    }
                }
            }

            // For writing practice: dispatch AI grading jobs asynchronously
            if ($set->quiz->skill === 'writing') {
                $attempt->load(['attemptAnswers.question']);
                $remainingCredits = $user->getRemainingWritingAiCredits();
                
                foreach ($attempt->attemptAnswers as $aa) {
                    if ($aa->grading_status === 'pending' && $aa->question) {
                        // Check credits
                        if ($remainingCredits !== 'unlimited' && $remainingCredits <= 0) {
                            Log::info("AI Limit reached for user {$user->id}, skipping auto-grading for answer {$aa->id}");
                            $aa->update(['grading_status' => 'limit_reached']);
                            continue;
                        }

                        ProcessWritingGrading::dispatch($aa->id, [
                            'part'       => $aa->question->part,
                            'word_limit' => $aa->question->metadata['word_limit'] ?? null,
                            'stem'       => $aa->question->stem,
                        ]);

                        // Record usage and decrement local counter
                        $user->recordWritingAiUsage($aa->question->part);
                        if ($remainingCredits !== 'unlimited') {
                            $remainingCredits--;
                        }
                    }
                }
            }

            return $attempt;
        });

        // Message Logic
        $message = 'Hoàn thành!';
        $redirectUrl = null;

        if ($set->quiz->skill === 'writing') {
            $message = 'Hoàn thành! Hãy kiểm tra đáp án gợi ý.';
            $redirectUrl = route('writingHistory.show', $attempt->id);
        } elseif (in_array($set->quiz->skill, ['reading', 'listening', 'grammar'])) {
            $redirectUrl = route('history.show', $attempt->id);
        } else {
            $redirectUrl = route('dashboard');
        }

        return response()->json([
            'success' => true,
            'redirect' => $redirectUrl,
            'score' => $finalScore,
            'message' => $message,
            'attempt_id' => $attempt->id,
            'answer_ids' => $set->quiz->skill === 'writing' ? clone $attempt->refresh()->attemptAnswers->pluck('id', 'question_id')->toArray() : []
        ]);
    }

    public function getAiUsageStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);

        $remaining = $user->getRemainingWritingAiCredits();
        
        $resetVersion = $user->ai_reset_version ?? 0;
        $used = WritingAiUsage::where('user_id', $user->id)
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');

        $defaultAiLimit = (int)(\App\Models\Setting::where('key', 'default_ai_limit')->value('value') ?? 10);
        $limit = $user->isAdmin() ? '∞' : ($defaultAiLimit + ($user->ai_extra_uses ?? 0));

        // Return same structure for all parts
        $status = [];
        for ($i = 1; $i <= 4; $i++) {
             $status[$i] = [
                 'used' => $user->isAdmin() ? '∞' : (int)$used,
                 'limit' => $limit,
                 'remaining' => $remaining
             ];
        }

        return response()->json($status);
    }

    public function gradeWriting(AttemptAnswer $answer, Request $request)
    {
        $user = $request->user();
        
        if ($answer->attempt->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $answer->load('question');
        if ($answer->question->skill !== 'writing') {
            return response()->json(['message' => 'Invalid question type.'], 400);
        }

        $remaining = $user->getRemainingWritingAiCredits();
        
        if ($remaining !== 'unlimited' && $remaining <= 0) {
            return response()->json(['message' => 'Bạn đã hết lượt chấm AI. Vui lòng liên hệ Admin để thêm lượt.'], 403);
        }

        try {
            // Check if it's already graded manually or by AI just now
            if ($answer->grading_status === 'graded') {
                 return response()->json(['message' => 'Bài này đã được chấm thủ công.'], 400);
            }

            $data = [
                'part' => $answer->question->part,
                'question' => $answer->question->stem,
                'metadata' => $answer->question->metadata,
                'answer' => $answer->answer
            ];

            $targetLevel = $user->target_level ?? 'B2';
            $result = $this->aiService->gradeWriting($data, $targetLevel);

            DB::transaction(function () use ($answer, $result, $user) {
                // Update answer
                $answer->update([
                    'ai_metadata' => $result,
                    'grading_status' => 'ai_graded'
                ]);

                // Process scoring based on AI result
                $aiScore = $result['feedback']['scores']['overall_score'] ?? $result['feedback']['overall_score'] ?? null;
                if ($aiScore !== null) {
                    $answer->update(['score' => $aiScore]);
                }

                // Record usage
                $user->recordWritingAiUsage($answer->question->part);
            });

            // Update attempt score locally
            $attempt = $answer->attempt;
            $attempt->refresh()->load('attemptAnswers.question');
            $totalEarned = $attempt->attemptAnswers->sum('score');
            $totalPossible = $attempt->attemptAnswers->sum(fn($a) => $a->question->point ?? 10);
            $attempt->update(['score' => $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 2) : 0]);

            // The original instruction seems to have intended this logic for a different method,
            // but applying it faithfully as requested within gradeWriting.
            // Note: $set is not directly available here, using $attempt->set.
            // Also, $attempt->set->quiz->skill will always be 'writing' in this method.
            if ($attempt->set->quiz->skill === 'reading' || $attempt->set->quiz->skill === 'listening' || $attempt->set->quiz->skill === 'grammar') {
                return response()->json([
                    'message' => 'Practice request submitted successfully.',
                    'redirect' => route('history.show', $attempt->id)
                ]);
            }

            return response()->json([
                'message' => 'Practice request submitted successfully.',
                'redirect' => route('skills.show', $attempt->set->quiz->skill) // Assuming $attempt->set->quiz->skill is the correct skill identifier
            ]);

        } catch (\Exception $e) {
            Log::error('Manual AI Grading Error: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi kết nối AI. Vui lòng thử lại sau.'], 500);
        }
    }
}
