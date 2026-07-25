<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\MockTest;
use App\Models\User;
use App\Models\WritingAiUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
                // So sánh datetime (không bọc DATE()) để dùng được index finished_at.
                if ($dateFrom) $q->where('finished_at', '>=', Carbon::parse($dateFrom)->startOfDay());
                if ($dateTo) $q->where('finished_at', '<=', Carbon::parse($dateTo)->endOfDay());
            }])
            // Đếm mock đã hoàn thành bằng subquery — không còn 1 query/user.
            ->withCount(['mockTests as completed_mock_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->all());

        // Preload AI usage cho cả trang trong 1 query (thay vì 1 query/user).
        $aiByUser = $this->aiUsageByUser($users->getCollection()->pluck('id'));

        $rows = $users->getCollection()->map(function ($user) use ($aiByUser) {
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

            return [
                'user'            => $user,
                'total'           => $attempts->count(),
                'avg_reading'     => $avg($bySkill('reading')),
                'avg_listening'   => $avg($bySkill('listening')),
                'avg_grammar'     => $avg($bySkill('grammar')),
                'avg_writing_mock'=> $avg($bySkill('writing', 'mock_test')),
                'mock_count'      => $user->completed_mock_count,
                'ai_used'         => $this->aiUsedFor($aiByUser, $user),
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
        $filename = 'class_report_' . now()->format('Ymd') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        // Stream + chunk: chỉ giữ 200 user (kèm attempts) trong RAM mỗi lượt thay
        // vì nạp TOÀN BỘ user + attempts một lần → tránh OOM/timeout khi lớp đông.
        $callback = function () {
            $h = fopen('php://output', 'w');
            fputs($h, "\xEF\xBB\xBF");
            fputcsv($h, ['Tên', 'Email', 'Tổng bài', 'Avg Reading', 'Avg Listening', 'Avg Grammar', 'Avg Writing (mock)', 'Mock Tests', 'AI dùng', 'Hết hạn']);

            User::where('role', 'user')
                ->withCount(['mockTests as completed_mock_count' => fn ($q) => $q->where('status', 'completed')])
                ->with(['attempts' => fn ($q) => $q->whereNotNull('score')])
                ->orderBy('name')
                ->chunk(200, function ($users) use ($h) {
                    $aiByUser = $this->aiUsageByUser($users->pluck('id'));

                    foreach ($users as $user) {
                        $attempts = $user->attempts;
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
                            $user->completed_mock_count,
                            $this->aiUsedFor($aiByUser, $user),
                            $user->expires_at?->format('d/m/Y') ?? 'N/A',
                        ]);
                    }
                });

            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Tổng AI usage theo (user, reset_version) trong 1 query, keyed theo user_id.
     */
    private function aiUsageByUser(Collection $userIds): Collection
    {
        return WritingAiUsage::whereIn('user_id', $userIds)
            ->selectRaw('user_id, reset_version, SUM(usage_count) as total')
            ->groupBy('user_id', 'reset_version')
            ->get()
            ->groupBy('user_id');
    }

    /** Lấy tổng AI usage đúng reset_version hiện tại của user. */
    private function aiUsedFor(Collection $aiByUser, User $user): int
    {
        $rows = $aiByUser->get($user->id);
        if (! $rows) {
            return 0;
        }

        return (int) ($rows->firstWhere('reset_version', $user->ai_reset_version ?? 0)->total ?? 0);
    }
}
