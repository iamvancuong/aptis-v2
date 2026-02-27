<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\WritingReview;
use Illuminate\Http\Request;

class WritingReviewController extends Controller
{
    /**
     * List all writing submissions, newest first.
     * Filter: all / pending / graded
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $query = Attempt::where('skill', 'writing')
            ->where('mode', 'mock_test')
            ->with(['user', 'set.quiz', 'attemptAnswers.writingReview', 'attemptAnswers.question']);

        if ($filter === 'pending') {
            $query->whereHas('attemptAnswers', function ($q) {
                $q->whereIn('grading_status', ['pending', 'ai_graded']);
            });
        } elseif ($filter === 'graded') {
            $query->whereDoesntHave('attemptAnswers', function ($q) {
                $q->whereIn('grading_status', ['pending', 'ai_graded']);
            });
        }

        $attempts = $query->latest('created_at')->paginate(20);

        return view('admin.writing-reviews.index', compact('attempts', 'filter'));
    }

    /**
     * Show a specific attempt's all writing answers for grading.
     */
    public function show(Attempt $attempt)
    {
        $attempt->load([
            'user',
            'set.quiz',
            'attemptAnswers' => function ($q) {
                $q->whereHas('question', function ($sq) {
                    $sq->where('skill', 'writing');
                });
                $q->with(['question', 'writingReview']);
                $q->orderBy('id');
            },
        ]);

        return view('admin.writing-reviews.show', compact('attempt'));
    }

    /**
     * Grade all writing answers for a given attempt.
     */
    public function grade(Request $request, Attempt $attempt)
    {
        $data = $request->validate([
            'scores' => 'required|array',
            'scores.*' => 'required|numeric|min:0|max:10',
            'comments' => 'nullable|array',
            'comments.*' => 'nullable|string|max:5000',
        ]);

        $writingAnswers = $attempt->attemptAnswers()
            ->whereHas('question', fn($q) => $q->where('skill', 'writing'))
            ->get();

        foreach ($writingAnswers as $answer) {
            $score = $data['scores'][$answer->id] ?? 0;
            $comment = $data['comments'][$answer->id] ?? null;

            WritingReview::updateOrCreate(
                ['attempt_answer_id' => $answer->id],
                [
                    'reviewer_id' => auth()->id(),
                    'total_score' => $score,
                    'comment' => $comment,
                ]
            );

            $answer->update([
                'grading_status' => 'graded',
                'score' => $score,
            ]);
        }

        // Calculate total score for the attempt
        $totalEarned = $writingAnswers->sum('score');
        $totalPossible = $writingAnswers->sum(fn($a) => $a->question->point ?? 10);
        $finalScore = $totalPossible > 0 ? ($totalEarned / $totalPossible) * 100 : 0;

        $attempt->update(['score' => $finalScore]);

        return redirect()->route('admin.writing-reviews.index')->with('success', 'Đã chấm bài Writing thành công!');
    }

    /**
     * Bulk approve: set grading_status = 'graded' for all ai_graded answers.
     */
    public function bulkApprove(Request $request)
    {
        $data = $request->validate(['attempt_ids' => 'required|array', 'attempt_ids.*' => 'integer']);

        $attempts = Attempt::whereIn('id', $data['attempt_ids'])
            ->where('skill', 'writing')
            ->with(['attemptAnswers.question'])
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
            $writingAnswers = $attempt->attemptAnswers
                ->filter(fn($a) => $a->grading_status === 'ai_graded');

            foreach ($writingAnswers as $answer) {
                // Use AI overall_score as the score
                $aiScore = $answer->ai_metadata['feedback']['overall_score'] ?? $answer->score;
                $answer->update(['grading_status' => 'graded', 'score' => $aiScore]);
            }

            if ($writingAnswers->count() > 0) {
                // Recalculate attempt score
                $attempt->refresh()->load('attemptAnswers.question');
                $totalEarned   = $attempt->attemptAnswers->sum('score');
                $totalPossible = $attempt->attemptAnswers->sum(fn($a) => $a->question->point ?? 10);
                $attempt->update(['score' => $totalPossible > 0 ? round($totalEarned / $totalPossible * 100, 2) : 0]);
                $count++;
            }
        }

        return redirect()->back()->with('success', "Đã duyệt {$count} bài Writing thành công!");
    }
}
