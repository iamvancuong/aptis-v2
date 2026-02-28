<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpeakingReviewController extends Controller
{
    /**
     * List all speaking attempts.
     * Filter: all / pending / graded
     */
    public function index(Request $request)
    {
        $filter = $request->query('status', 'pending');
        $search = $request->query('search');

        $query = Attempt::with(['user', 'set'])
            ->where('skill', 'speaking')
            ->where('mode', 'mock_test')
            ->where('is_grading_requested', true);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($filter === 'pending') {
            $query->whereHas('attemptAnswers', function ($q) {
                $q->whereIn('grading_status', ['pending']);
            });
        } elseif ($filter === 'graded') {
            $query->whereDoesntHave('attemptAnswers', function ($q) {
                $q->whereIn('grading_status', ['pending']);
            });
        }

        $attempts = $query->latest('grading_requested_at')->paginate(15)->appends($request->all());

        return view('admin.speaking-reviews.index', compact('attempts', 'filter', 'search'));
    }

    /**
     * Show the detailed grading page for a single speaking attempt.
     */
    public function show(Attempt $attempt)
    {
        if ($attempt->skill !== 'speaking') {
            abort(404);
        }

        $attempt->load(['user', 'set', 'attemptAnswers.question']);

        // Group the answers by physical parts for rendering
        $answers = $attempt->attemptAnswers->sortBy('question.part');

        return view('admin.speaking-reviews.show', compact('attempt', 'answers'));
    }

    /**
     * Process the manual grading submitted by the admin.
     * We expect an array of 'grades' keyed by attempt_answer_id.
     */
    public function grade(Request $request, Attempt $attempt)
    {
        if ($attempt->skill !== 'speaking') {
            abort(404);
        }

        $request->validate([
            'grades' => 'required|array',
            'grades.*.score' => 'required|numeric|min:0',
            'grades.*.feedback' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $attempt) {
            $totalScore = 0;
            $totalPossible = 0;

            foreach ($request->grades as $answerId => $gradeData) {
                $answer = AttemptAnswer::where('attempt_id', $attempt->id)->where('id', $answerId)->first();
                if (!$answer) continue;

                $maxPoint = $answer->question->point;
                $totalPossible += $maxPoint;

                $score = min((float)$gradeData['score'], $maxPoint);
                $totalScore += $score;

                $answer->update([
                    'score' => $score,
                    'is_correct' => $score > 0, // Roughly speaking
                    'feedback' => $gradeData['feedback'] ?? null,
                    'grading_status' => 'graded',
                ]);
            }

            // Calculate overall percentage
            $percentage = ($totalPossible > 0) ? ($totalScore / $totalPossible) * 100 : 0;

            $attempt->update([
                'score' => round($percentage, 2),
            ]);
        });

        return redirect()->route('admin.speaking-reviews.index')->with('success', 'Đã chấm điểm thành công bài Speaking!');
    }
}
