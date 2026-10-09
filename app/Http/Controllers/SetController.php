<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class SetController extends Controller
{
    public function index($skill, $part)
    {
        // Validate skill
        if (!in_array($skill, ['reading', 'listening', 'writing'])) {
            abort(404);
        }

        // Get the quiz for this skill + part
        $quiz = Quiz::where('skill', $skill)
            ->where('part', $part)
            ->where('is_published', true)
            ->firstOrFail();

        // Get all sets for this quiz with question counts
        $sets = $quiz->sets()
            ->where('is_public', true)
            ->withCount('questions')
            ->orderBy('order')
            ->get();

        // Kết quả của học viên trên từng đề: số lần làm + điểm cao nhất.
        // Một câu gom nhóm, không N+1.
        $setStats = \App\Models\Attempt::where('user_id', auth()->id())
            ->whereIn('set_id', $sets->pluck('id'))
            ->where('mode', 'practice')
            ->whereNotNull('finished_at')
            ->selectRaw('set_id, count(*) as times, max(score) as best')
            ->groupBy('set_id')
            ->get()
            ->keyBy('set_id');

        return view('sets.index', compact('skill', 'part', 'quiz', 'sets', 'setStats'));
    }

    // Màn "Xem đề" (sets.show) đã GỠ HẲN 10/2026: nó in toàn bộ câu hỏi
    // (kể cả bài nghe) ra một trang tĩnh → học viên copy/lộ đề. Học viên chỉ
    // tiếp cận đề qua màn luyện tập (practice.show). Đừng dựng lại.
}
