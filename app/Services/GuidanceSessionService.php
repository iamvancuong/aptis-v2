<?php

namespace App\Services;

use App\Mail\GuidanceHostMail;
use App\Mail\GuidanceLinkMail;
use App\Models\GuidanceBooking;
use App\Models\GuidanceSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Kích hoạt một buổi hướng dẫn: tạo phòng Zoom (nếu chưa có) rồi gửi link cho
 * học viên đã đặt lịch + admin. Idempotent — gọi lại chỉ gửi lại, không tạo
 * phòng mới.
 */
class GuidanceSessionService
{
    public function __construct(private ZoomService $zoom) {}

    /**
     * @return array{session: GuidanceSession, sent: int}
     */
    public function activateAndSend(Carbon $date): array
    {
        $dateStr = $date->toDateString();
        $startAt = Carbon::parse($dateStr . ' ' . config('guidance.time'));

        // Dùng whereDate: cột lưu dạng datetime nên so khớp theo ngày.
        $session = GuidanceSession::whereDate('session_date', $dateStr)->first()
            ?? new GuidanceSession(['session_date' => $dateStr]);

        // Tạo phòng 1 lần; lần sau dùng lại.
        if (! $session->hasRoom()) {
            $room = $this->zoom->createMeeting($startAt, 'Milaedu — Buổi hướng dẫn ' . $startAt->format('d/m/Y'));
            $session->fill([
                'zoom_meeting_id' => $room['meeting_id'],
                'join_url'        => $room['join_url'],
                'start_url'       => $room['start_url'],
                'passcode'        => $room['passcode'],
            ])->save();
        }

        // Người đã đặt lịch buổi này.
        $bookings = GuidanceBooking::with('user')
            ->whereDate('session_date', $dateStr)
            ->get()
            ->filter(fn ($b) => $b->user);

        foreach ($bookings as $booking) {
            Mail::to($booking->user->email)->send(new GuidanceLinkMail(
                sessionAt: $startAt,
                joinUrl: $session->join_url,
                passcode: $session->passcode,
            ));
        }

        // Admin (người dạy) nhận start_url.
        if (filled(config('zoom.admin_email'))) {
            Mail::to(config('zoom.admin_email'))->send(new GuidanceHostMail(
                sessionAt: $startAt,
                startUrl: $session->start_url,
                passcode: $session->passcode,
                studentEmails: $bookings->pluck('user.email')->all(),
            ));
        }

        $session->update(['sent_at' => now()]);

        return ['session' => $session, 'sent' => $bookings->count()];
    }
}
