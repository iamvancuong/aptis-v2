<?php

namespace App\Http\Controllers;

use App\Models\FullTest;
use App\Services\FullTestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Full Test phía học viên: trang giới thiệu, bắt đầu, màn chuyển phần, kết quả
 * và bảng điểm PDF. Phần làm bài dùng lại trang thi thử (MockTestController).
 */
class FullTestController extends Controller
{
    public function __construct(private FullTestService $service) {}

    public function index(Request $request)
    {
        $user = $request->user();

        return view('full-test.index', [
            'quota'     => $this->service->quotaFor($user),
            'remaining' => $this->service->remainingFor($user),
            'active'    => $this->service->activeFor($user),
            'history'   => FullTest::where('user_id', $user->id)->latest('id')->get(),
        ]);
    }

    public function start(Request $request)
    {
        $result = $this->service->start($request->user());

        if (is_string($result)) {
            return redirect()->route('full-test.index')->with('error', $result);
        }

        return redirect()->route('full-test.show', $result);
    }

    /** Màn tiến trình / chuyển phần. */
    public function show(FullTest $fullTest)
    {
        $this->authorizeOwner($fullTest);

        if ($fullTest->isCompleted()) {
            return redirect()->route('full-test.result', $fullTest);
        }

        $fullTest->load('mockTests');
        $currentSkill = $fullTest->currentSkill();
        $currentMock = $fullTest->stageMock($currentSkill);

        return view('full-test.show', [
            'fullTest'     => $fullTest,
            'stages'       => FullTest::stages(),
            'currentSkill' => $currentSkill,
            'currentMock'  => $currentMock,
            'labels'       => FullTestService::SKILL_LABELS,
        ]);
    }

    /** Vào phần đang tới lượt (tạo bài nếu chưa có). */
    public function next(FullTest $fullTest)
    {
        $this->authorizeOwner($fullTest);

        $result = $this->service->enterStage($fullTest);

        if (is_string($result)) {
            return redirect()->route('full-test.show', $fullTest)->with('error', $result);
        }

        return redirect()->route('mock-test.show', $result);
    }

    public function result(FullTest $fullTest)
    {
        $this->authorizeOwner($fullTest);

        if (! $fullTest->isCompleted()) {
            return redirect()->route('full-test.show', $fullTest);
        }

        $report = $this->service->report($fullTest);

        return view('full-test.result', [
            'fullTest' => $fullTest,
            'report'   => $report,
            'aim'      => $this->service->aim($fullTest->user ?? auth()->user(), $report),
            'pdfRoute' => route('full-test.pdf', $fullTest),
        ]);
    }

    public function pdf(FullTest $fullTest)
    {
        $this->authorizeOwner($fullTest);

        return $this->certificatePdf($this->service, $fullTest);
    }

    /**
     * Dùng chung cho học viên và admin. Chỉ xuất khi đã chấm xong cả 5 phần.
     */
    public static function certificatePdf(FullTestService $service, FullTest $fullTest)
    {
        $report = $service->report($fullTest);

        if (! $report['fully_graded']) {
            return back()->with('error', 'Bảng điểm chỉ xuất được khi đã chấm xong cả 5 phần.');
        }

        $fullTest->loadMissing('user');

        return Pdf::loadView('full-test.pdf', ['fullTest' => $fullTest, 'report' => $report])
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->download('bang-diem-full-test-' . $fullTest->code() . '.pdf');
    }

    private function authorizeOwner(FullTest $fullTest): void
    {
        abort_unless($fullTest->user_id === auth()->id(), 403);
    }
}
