<?php

namespace App\Http\Middleware;

use App\Models\LoginSession;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Giới hạn số thiết bị dùng ĐỒNG THỜI trên một tài khoản.
 *
 * Luật (cấu hình ở `config/devices.php`):
 *   - Tối đa `max_devices` thiết bị hoạt động cùng lúc.
 *   - Thiết bị vượt trần → +1 vi phạm, đá thiết bị lâu nhất, VẪN cho vào.
 *   - Đủ `block_after_violations` vi phạm → khoá tài khoản.
 *   - Vi phạm cũ hơn `violation_reset_days` ngày thì bỏ qua, đếm lại từ đầu.
 *
 * ⚠️ HAI LỖI CŨ ĐÃ VÁ Ở ĐÂY — đừng vô tình dựng lại:
 *
 * 1. ĐẾM CẢ LỊCH SỬ. Bản cũ `count()` mọi dòng `login_sessions` của user, mà dòng
 *    chỉ mất khi bấm Đăng xuất. Đóng trình duyệt không xoá gì; tab ẩn danh thì mất
 *    cookie nên mỗi lần mở lại là một "thiết bị" mới VĨNH VIỄN. Kết quả: một người
 *    ngồi một máy mở ẩn danh vài lần là bị khoá. Nay chỉ đếm phiên còn phát sinh
 *    request trong `activity_window_hours`.
 *
 * 2. TRA `device_id` KHÔNG KÈM `user_id`. Máy đã có dòng của tài khoản A, nay tài
 *    khoản B đăng nhập → dòng bị gán sang B rồi `return` sớm, BỎ QUA phép đếm của
 *    B. Bằng chứng trong DB: có tài khoản giữ 4 dòng dù trần là 3. Nay tra theo
 *    cặp (device_id, user_id) — unique cũng đã đổi theo cặp nên không còn phải
 *    cướp dòng của nhau.
 */
class SessionLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        /** @var User $user */
        $user = auth()->user();

        // Admin không bị giới hạn thiết bị.
        if ($user->isAdmin()) {
            return $next($request);
        }

        $deviceId    = $request->cookie('aptis_device_id');
        $isNewDevice = false;

        if (! $deviceId) {
            $deviceId    = hash('sha256', Str::uuid() . time());
            $isNewDevice = true;
        }

        // Thiết bị đã biết CỦA CHÍNH tài khoản này → chỉ cập nhật mốc hoạt động.
        $phienHienCo = LoginSession::where('device_id', $deviceId)
            ->where('user_id', $user->id)
            ->first();

        if ($phienHienCo) {
            $phienHienCo->update([
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'last_active_at' => now(),
            ]);

            return $next($request);
        }

        // Thiết bị mới với tài khoản này → xét trần.
        $moc = now()->subHours((int) config('devices.activity_window_hours'));

        $dangHoatDong = LoginSession::where('user_id', $user->id)
            ->where('last_active_at', '>=', $moc)
            ->count();

        $tran = $user->max_devices ?: (int) config('devices.max_devices');

        if ($dangHoatDong >= $tran) {
            if ($response = $this->xuLyViPham($user, $tran)) {
                return $response;
            }

            // Đá thiết bị lâu không dùng nhất để nhường suất cho thiết bị này.
            LoginSession::where('user_id', $user->id)
                ->orderBy('last_active_at')
                ->first()
                ?->delete();
        }

        LoginSession::create([
            'user_id'        => $user->id,
            'device_id'      => $deviceId,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'last_active_at' => now(),
        ]);

        $response = $next($request);

        // Gắn cookie qua headers thay vì withCookie(): response dạng stream hoặc
        // file (audio đề bài) là Symfony Response thuần, không có withCookie().
        if ($isNewDevice) {
            $response->headers->setCookie(cookie()->forever('aptis_device_id', $deviceId));
        }

        return $response;
    }

    /**
     * Ghi nhận vi phạm và khoá nếu đã đủ ngưỡng.
     *
     * Trả về Response khi tài khoản bị khoá (dừng request tại đây), null khi chỉ
     * cảnh báo và cho đi tiếp.
     */
    private function xuLyViPham(User $user, int $tran): ?Response
    {
        $hanReset = (int) config('devices.violation_reset_days');

        // Vi phạm gần nhất đã quá lâu → coi như làm lại từ đầu. `last_violation_at`
        // null là dữ liệu có trước khi có cột này; cũng tính là hết hạn.
        $daHetHan = $user->last_violation_at === null
            || $user->last_violation_at->lt(now()->subDays($hanReset));

        $soViPham = ($daHetHan ? 0 : (int) $user->violation_count) + 1;

        $user->forceFill([
            'violation_count'   => $soViPham,
            'last_violation_at' => now(),
        ])->save();

        $nguong = (int) config('devices.block_after_violations');

        if ($soViPham >= $nguong) {
            $user->update(['status' => 'blocked']);
            LoginSession::where('user_id', $user->id)->delete();
            auth()->logout();

            return redirect()->route('login')->with('error',
                "Tài khoản đã bị khoá do đăng nhập quá {$tran} thiết bị cùng lúc nhiều lần."
                . ' Vui lòng liên hệ giảng viên để được mở lại.');
        }

        $conLai = $nguong - $soViPham;

        session()->flash('warning',
            "Cảnh báo {$soViPham}/{$nguong}: tài khoản của bạn vừa đăng nhập trên thiết bị mới"
            . " trong khi đã có {$tran} thiết bị đang dùng. Hệ thống chỉ cho phép {$tran} thiết bị"
            . " cùng lúc — thiết bị lâu không dùng nhất đã bị đăng xuất."
            . " Vi phạm thêm {$conLai} lần nữa là tài khoản bị khoá.");

        return null;
    }
}
