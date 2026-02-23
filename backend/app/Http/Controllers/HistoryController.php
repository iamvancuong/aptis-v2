<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index()
    {
        $attempts = auth()->user()
            ->attempts()
            ->whereIn('skill', ['reading', 'listening'])
            ->where('mode', 'mock_test')
            ->with(['set.quiz'])
            ->latest()
            ->paginate(20);

        return view('history.index', [
            'attempts' => $attempts,
            'isWriting' => false,
            'title' => 'Lịch sử Trắc nghiệm'
        ]);
    }

    public function show(Attempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        if ($attempt->mock_test_id) {
            return redirect()->route('mock-test.result', $attempt->mock_test_id);
        }

        return redirect()->route('history.index')->with('success', 'Lịch sử chi tiết không áp dụng cho bài làm này.');
    }

    public function writingIndex()
    {
        $attempts = auth()->user()
            ->attempts()
            ->where('skill', 'writing')
            ->where('mode', 'mock_test')
            ->with(['set'])
            ->latest()
            ->paginate(20);

        return view('history.index', [
            'attempts' => $attempts,
            'isWriting' => true,
            'title' => 'Lịch sử Writing'
        ]);
    }

    public function writingShow(Attempt $attempt)
    {
        // Check if user owns this attempt
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // Only allow writing attempts
        if ($attempt->skill !== 'writing') {
            return redirect()->route('writingHistory.index');
        }

        $attempt->load(['attemptAnswers.question', 'attemptAnswers.writingReview.reviewer']);

        return view('history.show', compact('attempt'));
    }
}
