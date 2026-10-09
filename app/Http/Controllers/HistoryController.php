<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request, ['reading', 'listening', 'grammar', 'speaking'], 'quiz', 'Lịch sử Trắc nghiệm & Ngữ pháp');
    }

    /**
     * Danh sách bài làm có lọc — dùng chung cho 3 trang lịch sử (trắc nghiệm,
     * Writing, Speaking). Trước đây là 3 hàm copy y hệt nhau.
     *
     * $kind quyết định trang chi tiết + route của bộ lọc: quiz | writing | speaking.
     */
    private function listing(Request $request, array $skills, string $kind, string $title)
    {
        $mode     = $request->get('mode', 'all');
        $scoreMin = $request->get('score_min');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $query = auth()->user()
            ->attempts()
            ->whereIn('skill', $skills)
            ->with(['set.quiz']);

        // Bài thi thử lưu mode = 'mock' (MockTestController). Bản cũ lọc theo
        // 'mock_test' nên tab "Thi thử" luôn rỗng — giữ cả hai cho dữ liệu cũ.
        if ($mode === 'practice')      $query->where('mode', 'practice');
        elseif ($mode === 'mock_test') $query->whereIn('mode', ['mock', 'mock_test']);
        if ($scoreMin !== null && $scoreMin !== '') $query->where('score', '>=', (float) $scoreMin);
        if ($dateFrom) $query->where('finished_at', '>=', Carbon::parse($dateFrom)->startOfDay());
        if ($dateTo)   $query->where('finished_at', '<=', Carbon::parse($dateTo)->endOfDay());

        $attempts = $query->latest()->paginate(20)->appends($request->only('mode', 'score_min', 'date_from', 'date_to'));

        return view('history.index', compact('attempts', 'kind', 'title', 'mode', 'scoreMin', 'dateFrom', 'dateTo'));
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
        return $this->listing($request, ['writing'], 'writing', 'Lịch sử Writing');
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
        return $this->listing($request, ['speaking'], 'speaking', 'Lịch sử Speaking');
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

        // Tính năng "gửi giáo viên chấm bài" đang TẮT (nút đã ẩn hoàn toàn ở
        // giao diện). Chặn luôn ở backend để không ai gọi thẳng route này tạo đơn
        // chấm phí. Bật lại bằng `aptis.teacher_grading_enabled`.
        if (! config('aptis.teacher_grading_enabled')) {
            return back()->with('info', 'Tính năng gửi giáo viên chấm bài đang tạm ngừng.');
        }

        if (!in_array($attempt->skill, ['writing', 'speaking']) || !in_array($attempt->mode, ['mock', 'mock_test'])) {
            return back()->with('error', 'Chỉ có thể yêu cầu chấm điểm cho bài thi Mock Test Writing hoặc Speaking.');
        }

        if ($attempt->is_grading_requested) {
            return back()->with('info', 'Bài này đã được yêu cầu chấm điểm.');
        }

        // Admin: chấm miễn phí, bật cờ ngay.
        if (auth()->user()->isAdmin()) {
            $attempt->update([
                'is_grading_requested' => true,
                'grading_requested_at' => now(),
            ]);

            return back()->with('success', 'Đã gởi yêu cầu chấm điểm cho giáo viên thành công!');
        }

        // Học viên: mỗi lần gửi giáo viên chấm là một đơn 100k.
        // Nếu đã có đơn chờ thanh toán cho bài này thì dùng lại, tránh tạo trùng.
        $existing = \App\Models\Order::where('user_id', auth()->id())
            ->where('type', \App\Models\Order::TYPE_GRADING)
            ->where('status', \App\Models\Order::STATUS_PENDING)
            ->where('meta->attempt_id', $attempt->id)
            ->latest()
            ->first();

        $order = $existing ?? \App\Models\Order::create([
            'order_code' => \App\Models\Order::generateCode(),
            'email'      => auth()->user()->email,
            'type'       => \App\Models\Order::TYPE_GRADING,
            'amount'     => (int) config('pricing.grading_price'),
            'status'     => \App\Models\Order::STATUS_PENDING,
            'user_id'    => auth()->id(),
            'meta'       => ['attempt_id' => $attempt->id, 'skill' => $attempt->skill],
        ]);

        return redirect()->to(\Illuminate\Support\Facades\URL::signedRoute('payment.show', $order));
    }
}
