<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $mode      = $request->get('mode', 'all');
        $scoreMin  = $request->get('score_min');
        $dateFrom  = $request->get('date_from');
        $dateTo    = $request->get('date_to');

        $query = auth()->user()
            ->attempts()
            ->whereIn('skill', ['reading', 'listening', 'grammar'])
            ->with(['set.quiz', 'mockTest']);

        if ($mode === 'practice')   $query->where('mode', 'practice');
        elseif ($mode === 'mock_test') $query->where('mode', 'mock_test');
        if ($scoreMin !== null && $scoreMin !== '') $query->where('score', '>=', (float) $scoreMin);
        if ($dateFrom) $query->whereDate('finished_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('finished_at', '<=', $dateTo);

        $attempts = $query->latest()->paginate(20)->appends(request()->only('mode','score_min','date_from','date_to'));

        return view('history.index', [
            'attempts'  => $attempts,
            'isWriting' => false,
            'title'     => 'Lịch sử Trắc nghiệm & Ngữ pháp',
            'mode'      => $mode,
            'scoreMin'  => $scoreMin,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    public function show(Attempt $attempt)
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        // R/L mock_test → Mock Test result page
        if ($attempt->mock_test_id) {
            return redirect()->route('mock-test.result', $attempt->mock_test_id);
        }

        // R/L/G practice with answers saved → show detail
        if (in_array($attempt->skill, ['reading', 'listening', 'grammar'])) {
            $attempt->load(['attemptAnswers.question', 'set.quiz']);
            return view('history.result', compact('attempt'));
        }

        return redirect()->route('history.index');
    }

    public function writingIndex(Request $request)
    {
        $mode     = $request->get('mode', 'all');
        $scoreMin = $request->get('score_min');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = auth()->user()
            ->attempts()
            ->where('skill', 'writing')
            ->with(['set']);

        if ($mode === 'practice')   $query->where('mode', 'practice');
        elseif ($mode === 'mock_test') $query->where('mode', 'mock_test');
        if ($scoreMin !== null && $scoreMin !== '') $query->where('score', '>=', (float) $scoreMin);
        if ($dateFrom) $query->whereDate('finished_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('finished_at', '<=', $dateTo);

        $attempts = $query->latest()->paginate(20)->appends(request()->only('mode','score_min','date_from','date_to'));

        return view('history.index', [
            'attempts'  => $attempts,
            'isWriting' => true,
            'title'     => 'Lịch sử Writing',
            'mode'      => $mode,
            'scoreMin'  => $scoreMin,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    public function writingShow(Attempt $attempt)
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        if ($attempt->skill !== 'writing') {
            return redirect()->route('writingHistory.index');
        }

        $attempt->load(['attemptAnswers.question', 'attemptAnswers.writingReview.reviewer']);

        return view('history.show', compact('attempt'));
    }
}
