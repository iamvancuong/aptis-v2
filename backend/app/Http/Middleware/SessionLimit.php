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
        $deviceId = $this->getDeviceId($request);

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

            // Block if violations >= 3
            if ($user->violation_count >= 3) {
                $user->update(['status' => 'blocked']);
                auth()->logout();
                return redirect()->route('login')->with('error', 'Tài khoản đã bị khóa do vi phạm đăng nhập quá nhiều thiết bị.');
            }

            // Remove oldest session
            LoginSession::where('user_id', $user->id)
                ->orderBy('last_active_at', 'asc')
                ->first()
                ?->delete();
        }

        // Create new session for this device
        LoginSession::create([
            'user_id' => $user->id,
            'device_id' => $deviceId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }

    private function getDeviceId(Request $request): string
    {
        // Generate unique device ID based on IP + User Agent
        return hash('sha256', $request->ip() . $request->userAgent());
    }
}
