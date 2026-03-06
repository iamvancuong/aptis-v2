<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\MockTest;
use App\Models\User;
use App\Models\WritingAiUsage;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $skill    = $request->get('skill', 'all');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $perPage  = $request->integer('per_page', 20);

        $users = User::where('role', 'user')
            ->with(['attempts' => function ($q) use ($skill, $dateFrom, $dateTo) {
                $q->whereNotNull('score');
                if ($skill !== 'all') $q->where('skill', $skill);
                if ($dateFrom) $q->whereDate('finished_at', '>=', $dateFrom);
                if ($dateTo) $q->whereDate('finished_at', '<=', $dateTo);
            }])
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->all());

        $rows = $users->getCollection()->map(function ($user) {
            $attempts = $user->attempts;

            $bySkill = function (string $sk, ?string $mode = null) use ($attempts) {
                $q = $attempts->where('skill', $sk);
                if ($mode === 'mock_test') {
                    $q = $q->whereIn('mode', ['mock', 'mock_test']);
                } elseif ($mode) {
                    $q = $q->where('mode', $mode);
                }
                return $q;
            };

            $avg = fn($col) => $col->count() > 0 ? round($col->avg('score'), 1) : null;

            $aiUsed = WritingAiUsage::where('user_id', $user->id)
                ->where('reset_version', $user->ai_reset_version ?? 0)
                ->sum('usage_count');

            return [
                'user'            => $user,
                'total'           => $attempts->count(),
                'avg_reading'     => $avg($bySkill('reading')),
                'avg_listening'   => $avg($bySkill('listening')),
                'avg_grammar'     => $avg($bySkill('grammar')),
                'avg_writing_mock'=> $avg($bySkill('writing', 'mock_test')),
                'mock_count'      => MockTest::where('user_id', $user->id)->where('status', 'completed')->count(),
                'ai_used'         => $aiUsed,
                'expires_at'      => $user->expires_at,
                'expiry_status'   => $user->expirationStatus(),
            ];
        });

        $users->setCollection($rows);

        return view('admin.reports.index', [
            'rows' => $users,
            'skill' => $skill,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ]);
    }

    public function export(Request $request)
    {
        ob_start();
        $rows = $this->index($request)->getData()['rows'] ?? [];
        ob_end_clean();

        // Re-run query for export
        $skill    = $request->get('skill', 'all');
        $users = User::where('role', 'user')->with('attempts')->orderBy('name')->get();

        $filename = 'class_report_' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $h = fopen('php://output', 'w');
            fputs($h, "\xEF\xBB\xBF");
            fputcsv($h, ['Tên', 'Email', 'Tổng bài', 'Avg Reading', 'Avg Listening', 'Avg Grammar', 'Avg Writing (mock)', 'Mock Tests', 'AI dùng', 'Hết hạn']);
            foreach ($users as $user) {
                $attempts = $user->attempts->whereNotNull('score');
                $avg = fn($sk, $m = null) => ($m === 'mock_test'
                    ? $attempts->where('skill', $sk)->whereIn('mode', ['mock', 'mock_test'])
                    : ($m ? $attempts->where('skill', $sk)->where('mode', $m) : $attempts->where('skill', $sk))
                )->avg('score');

                fputcsv($h, [
                    $user->name,
                    $user->email,
                    $attempts->count(),
                    number_format($avg('reading') ?? 0, 1),
                    number_format($avg('listening') ?? 0, 1),
                    number_format($avg('grammar') ?? 0, 1),
                    number_format($avg('writing', 'mock_test') ?? 0, 1),
                    MockTest::where('user_id', $user->id)->where('status', 'completed')->count(),
                    WritingAiUsage::where('user_id', $user->id)->where('reset_version', $user->ai_reset_version ?? 0)->sum('usage_count'),
                    $user->expires_at?->format('d/m/Y') ?? 'N/A',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }
}
