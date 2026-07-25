<?php

namespace App\Http\Controllers;

use App\Mail\GuidanceBookingMail;
use App\Models\GuidanceBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Đặt lịch buổi hướng dẫn 19h30 thứ 7. Học viên chỉ chọn được các thứ 7 nằm
 * trong thời hạn tài khoản của mình, và chỉ giữ 1 lịch (đổi được).
 */
class GuidanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Tài khoản cũ vĩnh viễn (không thời hạn) không có buổi hướng dẫn.
        if (! $user->canBookGuidance()) {
            return redirect()->route('dashboard');
        }

        return view('guidance.index', [
            'saturdays' => $this->eligibleSaturdays($user),
            'booking'   => GuidanceBooking::where('user_id', $user->id)->first(),
            'timeLabel' => config('guidance.time_label'),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->canBookGuidance()) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'session_date' => 'required|date',
        ]);

        $chosen   = Carbon::parse($data['session_date'])->toDateString();
        $eligible = $this->eligibleSaturdays($user)->map->toDateString();

        if (! $eligible->contains($chosen)) {
            return back()->withErrors([
                'session_date' => 'Ngày không hợp lệ — chỉ được chọn thứ 7 nằm trong thời hạn tài khoản của bạn.',
            ]);
        }

        GuidanceBooking::updateOrCreate(
            ['user_id' => $user->id],
            ['session_date' => $chosen],
        );

        // Chỉ xác nhận — link Zoom gửi riêng trước buổi (xem GuidanceSessionService).
        Mail::to($user->email)->send(new GuidanceBookingMail(
            email: $user->email,
            sessionAt: Carbon::parse($chosen)->setTimeFromTimeString(config('guidance.time')),
        ));

        return redirect()->route('guidance.index')
            ->with('success', 'Đã đặt lịch! Thông tin buổi học và link Zoom đã được gửi về email của bạn.');
    }

    /**
     * Danh sách các thứ 7 (19h30 còn ở tương lai) nằm trong thời hạn tài khoản.
     * Tài khoản không hạn → hiện vài tuần tới.
     *
     * @return Collection<int, Carbon>
     */
    private function eligibleSaturdays($user): Collection
    {
        // Không có thời hạn → không có buổi nào (tài khoản vĩnh viễn).
        if (! $user->expires_at) {
            return collect();
        }

        $time = config('guidance.time');
        $end  = $user->expires_at->copy();

        $cursor = Carbon::today();
        if (! $cursor->isSaturday()) {
            $cursor = $cursor->next(Carbon::SATURDAY);
        }

        $saturdays = collect();
        while ($cursor->lte($end)) {
            if ($cursor->copy()->setTimeFromTimeString($time)->isFuture()) {
                $saturdays->push($cursor->copy());
            }
            $cursor->addWeek();
        }

        return $saturdays;
    }
}
