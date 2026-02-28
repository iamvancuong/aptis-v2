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
            if ($set->quiz->skill === 'speaking' && $request->hasFile('speaking_audio')) {
                $audioFiles = $request->file('speaking_audio');
                foreach ($audioFiles as $qId => $files) {
                    $savedPaths = [];
                    foreach ($files as $idx => $file) {
                        $path = $file->store('speaking_attempts', 'public');
                        $savedPaths[] = $path;
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
                foreach ($attempt->attemptAnswers as $aa) {
                    if ($aa->grading_status === 'pending' && $aa->question) {
                        ProcessWritingGrading::dispatch($aa->id, [
                            'part'       => $aa->question->part,
                            'word_limit' => $aa->question->metadata['word_limit'] ?? null,
                            'stem'       => $aa->question->stem,
                        ]);
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
            $redirectUrl = null;
        } else {
            // Objective skill in practice mode -> no detailed view
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

        $resetVersion = $user->ai_reset_version ?? 0;
        $aiUsagesCount = WritingAiUsage::where('user_id', $user->id)
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');

        $limit = 10 + ($user->ai_extra_uses ?? 0);
        $remaining = max(0, $limit - $aiUsagesCount);

        if ($user->isAdmin()) {
            $remaining = 'unlimited';
        }

        // Return same structure for all parts
        $status = [];
        for ($i = 1; $i <= 4; $i++) {
             $status[$i] = [
                 'used' => $aiUsagesCount,
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

        $resetVersion = $user->ai_reset_version ?? 0;
        $aiUsagesCount = WritingAiUsage::where('user_id', $user->id)
            ->where('reset_version', $resetVersion)
            ->sum('usage_count');

        $limit = 10 + ($user->ai_extra_uses ?? 0);
        
        if (!$user->isAdmin() && $aiUsagesCount >= $limit) {
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

            $result = $this->aiService->gradeWriting($data);

            DB::transaction(function () use ($answer, $result, $user, $resetVersion) {
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
                if (!$user->isAdmin()) {
                    $usage = WritingAiUsage::firstOrCreate([
                        'user_id' => $user->id,
                        'writing_part' => $answer->question->part,
                        'reset_version' => $resetVersion
                    ]);
                    $usage->increment('usage_count');
                }
            });

            // Update attempt score locally
            $attempt = $answer->attempt;
            $attempt->refresh()->load('attemptAnswers.question');
            $totalEarned = $attempt->attemptAnswers->sum('score');
            $totalPossible = $attempt->attemptAnswers->sum(fn($a) => $a->question->point ?? 10);
            $attempt->update(['score' => $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 2) : 0]);

            return response()->json([
                'success' => true,
                'review' => $result['feedback']
            ]);

        } catch (\Exception $e) {
            Log::error('Manual AI Grading Error: ' . $e->getMessage());
            return response()->json(['message' => 'Lỗi kết nối AI. Vui lòng thử lại sau.'], 500);
        }
    }
}
