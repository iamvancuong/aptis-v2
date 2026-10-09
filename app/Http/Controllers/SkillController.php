<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function show($skill)
    {
        // Validate skill
        if (!in_array($skill, ['reading', 'listening', 'writing', 'grammar', 'speaking'])) {
            abort(404);
        }

        // Grammar doesn't use parts → redirect to grammar index
        if ($skill === 'grammar') {
            return redirect()->route('grammar.index');
        }

        // Get all quizzes (parts) for this skill
        $quizzes = Quiz::where('skill', $skill)
            ->where('is_published', true)
            ->withCount(['sets as public_sets_count' => fn ($q) => $q->where('is_public', true)])
            ->orderBy('part')
            ->get();

        // Tiến độ từng Part của học viên: số đề đã làm (khác nhau) + điểm TB.
        // Một câu gom nhóm qua bảng sets, không N+1.
        $userId = auth()->id();
        $partProgress = \App\Models\Attempt::query()
            ->join('sets', 'sets.id', '=', 'attempts.set_id')
            ->where('attempts.user_id', $userId)
            ->where('attempts.skill', $skill)
            ->where('attempts.mode', 'practice')
            ->whereIn('sets.quiz_id', $quizzes->pluck('id'))
            ->selectRaw('sets.quiz_id, count(distinct attempts.set_id) as done, avg(attempts.score) as avg_score')
            ->groupBy('sets.quiz_id')
            ->get()
            ->keyBy('quiz_id');

        $skillStats = [
            'attempts'   => \App\Models\Attempt::where('user_id', $userId)->where('skill', $skill)->whereNotNull('finished_at')->count(),
            'last_mock'  => \App\Models\Attempt::where('user_id', $userId)->where('skill', $skill)->where('mode', 'mock')
                ->whereNotNull('score')->latest('id')->value('score'),
        ];

        return view('skills.show', compact('skill', 'quizzes', 'partProgress', 'skillStats'));
    }
}
