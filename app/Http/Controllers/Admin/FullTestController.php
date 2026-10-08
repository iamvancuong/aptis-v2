<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FullTestController as UserFullTestController;
use App\Models\FullTest;
use App\Models\User;
use App\Services\FullTestService;
use Illuminate\Http\Request;

/**
 * Quản lý Full Test: danh sách lượt thi, xem bảng điểm, chấm lại phần AI treo,
 * và cấp lượt Full Test cho từng tài khoản.
 */
class FullTestController extends Controller
{
    public function __construct(private FullTestService $service) {}

    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $q = trim((string) $request->get('q', ''));

        $fullTests = FullTest::with(['user', 'mockTests.attempts.attemptAnswers.question'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->whereHas('user', fn ($u) => $u
                ->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $reports = $fullTests->getCollection()
            ->mapWithKeys(fn (FullTest $ft) => [$ft->id => $this->service->report($ft)]);

        return view('admin.full-tests.index', compact('fullTests', 'reports', 'status', 'q'));
    }

    public function show(FullTest $fullTest)
    {
        $fullTest->load('user');

        return view('admin.full-tests.show', [
            'fullTest' => $fullTest,
            'report'   => $this->service->report($fullTest),
            'pdfRoute' => route('admin.full-tests.pdf', $fullTest),
        ]);
    }

    public function pdf(FullTest $fullTest)
    {
        return UserFullTestController::certificatePdf($this->service, $fullTest);
    }

    public function regrade(FullTest $fullTest)
    {
        $count = $this->service->retryPendingGrading($fullTest);

        return back()->with('success', $count > 0
            ? "Đã gửi chấm lại {$count} phần. Kết quả cập nhật sau vài phút."
            : 'Không có phần nào cần chấm lại.');
    }

    /** Danh sách tài khoản + số lượt Full Test (đã dùng / được cấp). */
    public function quotas(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $users = User::where('role', 'user')
            ->when($q !== '', fn ($query) => $query->where(fn ($u) => $u
                ->where('email', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%")))
            ->withCount('fullTests')
            ->orderByDesc('full_tests_count')
            ->orderBy('email')
            ->paginate(30)
            ->withQueryString();

        return view('admin.full-tests.quotas', compact('users', 'q'));
    }

    public function updateQuota(Request $request, User $user)
    {
        $data = $request->validate([
            'full_test_quota' => 'required|integer|min:0|max:1000',
        ]);

        // forceFill: cột không nằm trong $fillable — chỉ admin đổi qua đây.
        $user->forceFill(['full_test_quota' => $data['full_test_quota']])->save();

        return back()->with('success', "Đã đặt {$user->email}: {$data['full_test_quota']} lượt Full Test.");
    }
}
