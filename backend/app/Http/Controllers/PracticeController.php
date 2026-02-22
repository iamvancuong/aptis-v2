<?php

namespace App\Http\Controllers;

use App\Models\Set;
use App\Services\GradingService;
use Illuminate\Http\Request;

class PracticeController extends Controller
{
    public function __construct(
        private GradingService $gradingService
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
            'answers' => 'required|array',
            'duration' => 'nullable|integer',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
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
        $result = $this->gradingService->gradeSet($questions, $data['answers'], 'practice');

        $totalPossiblePoints = $result['total_possible'];
        $finalScore = $result['percentage'];

        $attempt = \App\Models\Attempt::create([
            'user_id' => $user->id,
            'skill' => $set->quiz->skill ?? 'reading',
            'mode' => 'practice',
            'set_id' => $set->id,
            'started_at' => now()->subSeconds($request->input('duration', 0)),
            'finished_at' => now(),
            'duration_seconds' => $request->input('duration', 0),
            'score' => $finalScore,
        ]);

        $attempt->attemptAnswers()->createMany($result['attempt_answers']);

        // Message Logic
        $message = 'Hoàn thành!';
        $redirectUrl = null;

        if ($set->quiz->skill === 'writing') {
            $message = 'Hoàn thành! Hãy kiểm tra đáp án gợi ý.';
            $redirectUrl = null;
        } else {
            $redirectUrl = route('attempts.show', $attempt->id);
        }

        return response()->json([
            'success' => true,
            'redirect' => $redirectUrl,
            'score' => $finalScore,
            'message' => $message
        ]);
    }
}
