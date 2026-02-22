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

        $query = AttemptAnswer::whereHas('question', function ($q) {
            $q->where('skill', 'writing');
        })
        ->with(['attempt.user', 'attempt.set.quiz', 'question', 'writingReview.reviewer']);

        if ($filter === 'pending') {
            $query->where('grading_status', 'pending');
        } elseif ($filter === 'graded') {
            $query->where('grading_status', 'graded');
        }

        $submissions = $query->latest('created_at')->paginate(20);

        return view('admin.writing-reviews.index', compact('submissions', 'filter'));
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
     * Grade a single attempt answer (one writing part).
     */
    public function grade(Request $request, AttemptAnswer $attemptAnswer)
    {
        $data = $request->validate([
            'total_score' => 'required|numeric|min:0|max:10',
            'comment' => 'nullable|string|max:5000',
        ]);

        // Create or update the writing review
        WritingReview::updateOrCreate(
            ['attempt_answer_id' => $attemptAnswer->id],
            [
                'reviewer_id' => auth()->id(),
                'total_score' => $data['total_score'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        // Mark this answer as graded
        $attemptAnswer->update([
            'grading_status' => 'graded',
            'score' => $data['total_score'],
        ]);

        // Check if ALL writing answers in this attempt are graded
        $attempt = $attemptAnswer->attempt;
        $writingAnswers = $attempt->attemptAnswers()
            ->whereHas('question', fn($q) => $q->where('skill', 'writing'))
            ->get();

        $allGraded = $writingAnswers->every(fn($a) => $a->grading_status === 'graded');

        if ($allGraded) {
            // Calculate total score for the attempt
            $totalEarned = $writingAnswers->sum('score');
            $totalPossible = $writingAnswers->sum(fn($a) => $a->question->point ?? 10);
            $finalScore = $totalPossible > 0 ? ($totalEarned / $totalPossible) * 100 : 0;

            $attempt->update(['score' => $finalScore]);
        }

        return redirect()->back()->with('success', 'Đã lưu đánh giá thành công!');
    }
}
