<?php

namespace App\Http\Middleware;

use App\Models\LoginSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SessionLimit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        // Admins are exempt from session limits
        if ($user->isAdmin()) {
            return $next($request);
        }

        $deviceId = $request->cookie('aptis_device_id');
        $isNewDevice = false;

        if (!$deviceId) {
            $deviceId = hash('sha256', Str::uuid() . time());
            $isNewDevice = true;
        }

        // Update or create session for this device
        $existingSession = LoginSession::where('device_id', $deviceId)->first();

        if ($existingSession) {
            // Update last active time
            $existingSession->update([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_active_at' => now()
            ]);
            return $next($request);
        }

        // Count active sessions for this user
        $activeSessions = LoginSession::where('user_id', $user->id)->count();

        if ($activeSessions >= $user->max_devices) {
            // Increment violation count
            $user->increment('violation_count');
            $newViolationCount = $user->violation_count;

            // Block if violations >= 3
            if ($newViolationCount >= 3) {
                $user->update(['status' => 'blocked']);
                auth()->logout();
                return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa do vi phạm đăng nhập quá nhiều thiết bị (hệ thống chỉ cho phép đăng nhập đồng thời 1 thiết bị).');
            }

            // Remove oldest session (kicking out the previous device)
            LoginSession::where('user_id', $user->id)
                ->orderBy('last_active_at', 'asc')
                ->first()
                ?->delete();
                
            // Set alert message for the new session
            session()->flash('warning', "Cảnh báo: Bạn vừa đăng nhập trên thiết bị mới trong khi thiết bị cũ vẫn đang hoạt động. Đây là vi phạm lần {$newViolationCount}/3. Hệ thống không cho phép dùng 2 thiết bị cùng lúc. Nếu vi phạm 3 lần, tài khoản sẽ bị khóa.");
        }

        // Create new session for this device
        LoginSession::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_active_at' => now(),
        ]);

        $response = $next($request);
        
        // Attach the device ID cookie if it's new
        if ($isNewDevice) {
            $response->withCookie(cookie()->forever('aptis_device_id', $deviceId));
        }

        return $response;
    }

}
