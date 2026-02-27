<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MockTest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MockTestController extends Controller
{
    public function index(Request $request)
    {
        $skill  = $request->get('skill', 'all');
        $status = $request->get('status', 'all');

        $query = MockTest::with(['user'])
            ->latest('created_at');

        if ($skill !== 'all') {
            $query->where('skill', $skill);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $mockTests = $query->paginate(25)->appends($request->only('skill', 'status'));

        $stats = [
            'total'       => MockTest::count(),
            'completed'   => MockTest::where('status', 'completed')->count(),
            'in_progress' => MockTest::where('status', 'in_progress')->count(),
        ];

        return view('admin.mock-tests.index', compact('mockTests', 'skill', 'status', 'stats'));
    }

    public function export(Request $request)
    {
        $skill  = $request->get('skill', 'all');
        $status = $request->get('status', 'all');

        $query = MockTest::with(['user'])->where('status', 'completed');

        if ($skill !== 'all') {
            $query->where('skill', $skill);
        }

        $rows = $query->latest()->get();

        $filename = 'mock_tests_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', 'Học sinh', 'Email', 'Kỹ năng', 'Điểm (%)', 'Thời gian (giây)', 'Ngày thi']);

            foreach ($rows as $mt) {
                fputcsv($handle, [
                    $mt->id,
                    $mt->user->name ?? 'N/A',
                    $mt->user->email ?? 'N/A',
                    ucfirst($mt->skill),
                    number_format($mt->score ?? 0, 1),
                    $mt->duration_seconds ?? 0,
                    $mt->finished_at?->format('d/m/Y H:i') ?? 'N/A',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
