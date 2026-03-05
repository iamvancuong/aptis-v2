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
            ->whereIn('skill', ['reading', 'listening', 'grammar', 'speaking'])
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

        // If it's Writing or Speaking, we want the detail view (teacher feedback)
        // even if it belongs to a Mock Test.
        if ($attempt->skill === 'writing') {
            return $this->writingShow($attempt);
        }
        if ($attempt->skill === 'speaking') {
            return $this->speakingShow($attempt);
        }

        // For other skills (R/L/G), if it's a Mock Test, always show the mock test result page (which has inline detail now)
        if ($attempt->mock_test_id) {
            return redirect()->route('mock-test.result', $attempt->mock_test_id);
        }

        // For practice attempts of R/L/G, show the detail result view
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

        if (!$attempt->is_seen && $attempt->score !== null) {
            $attempt->update(['is_seen' => true]);
        }

        return view('history.show', compact('attempt'));
    }

    public function speakingIndex(Request $request)
    {
        $mode     = $request->get('mode', 'all');
        $scoreMin = $request->get('score_min');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = auth()->user()
            ->attempts()
            ->where('skill', 'speaking')
            ->with(['set']);

        if ($mode === 'practice')   $query->where('mode', 'practice');
        elseif ($mode === 'mock_test') $query->where('mode', 'mock_test');
        if ($scoreMin !== null && $scoreMin !== '') $query->where('score', '>=', (float) $scoreMin);
        if ($dateFrom) $query->whereDate('finished_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('finished_at', '<=', $dateTo);

        $attempts = $query->latest()->paginate(20)->appends(request()->only('mode','score_min','date_from','date_to'));

        return view('history.index', [
            'attempts'  => $attempts,
            'isSpeaking' => true,
            'title'     => 'Lịch sử Speaking',
            'mode'      => $mode,
            'scoreMin'  => $scoreMin,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    public function speakingShow(Attempt $attempt)
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        if ($attempt->skill !== 'speaking') {
            return redirect()->route('speakingHistory.index');
        }

        $attempt->load(['attemptAnswers.question']);

        if (!$attempt->is_seen && $attempt->score !== null) {
            $attempt->update(['is_seen' => true]);
        }

        return view('history.speaking-show', compact('attempt'));
    }

    public function requestGrading(Request $request, Attempt $attempt)
    {
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($attempt->skill, ['writing', 'speaking']) || !in_array($attempt->mode, ['mock', 'mock_test'])) {
            return back()->with('error', 'Chỉ có thể yêu cầu chấm điểm cho bài thi Mock Test Writing hoặc Speaking.');
        }

        if ($attempt->is_grading_requested) {
            return back()->with('info', 'Bài này đã được yêu cầu chấm điểm.');
        }

        if (!auth()->user()->isAdmin()) {
            // Check limit of configured key per skill
            $limitKey = $attempt->skill . '_grading_limit';
            $maxLimit = \App\Models\Setting::where('key', $limitKey)->value('value') ?? 2;

            $count = Attempt::where('user_id', auth()->id())
                ->where('skill', $attempt->skill)
                ->where('is_grading_requested', true)
                ->count();

            if ($count >= $maxLimit) {
                return back()->with('error', "Bạn đã dùng hết lượt yêu cầu chấm điểm cho kỹ năng " . ucfirst($attempt->skill) . " (Tối đa {$maxLimit} lần).");
            }
        }

        $attempt->update([
            'is_grading_requested' => true,
            'grading_requested_at' => now(),
        ]);

        return back()->with('success', 'Đã gởi yêu cầu chấm điểm cho giáo viên thành công!');
    }
}
