<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSession;
use App\Services\GuidanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Quản lý buổi hướng dẫn: xem các thứ 7 sắp tới có người đăng ký và bấm
 * "Tạo phòng & gửi" (ngoài việc cron tự chạy trước buổi).
 */
class GuidanceSessionController extends Controller
{
    public function index()
    {
        // Các thứ 7 sắp tới có ít nhất 1 người đăng ký.
        $rows = GuidanceBooking::selectRaw('session_date, count(*) as cnt')
            ->whereDate('session_date', '>=', today())
            ->groupBy('session_date')
            ->orderBy('session_date')
            ->get();

        $sessions = GuidanceSession::whereIn('session_date', $rows->pluck('session_date'))
            ->get()
            ->keyBy(fn ($s) => $s->session_date->toDateString());

        return view('admin.guidance-sessions.index', [
            'rows'     => $rows,
            'sessions' => $sessions,
            'fake'     => config('zoom.fake'),
        ]);
    }

    public function activate(Request $request, GuidanceSessionService $service)
    {
        $data = $request->validate(['session_date' => 'required|date']);

        try {
            $result = $service->activateAndSend(Carbon::parse($data['session_date']));
        } catch (\Throwable $e) {
            return back()->with('error', 'Không tạo được phòng Zoom: ' . $e->getMessage());
        }

        return back()->with('success', "Đã tạo phòng và gửi link cho {$result['sent']} học viên + admin.");
    }
}
