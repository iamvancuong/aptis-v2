<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Chống dò mật khẩu: tối đa 5 lần sai / phút cho mỗi cặp email+IP, và 20 lần
        // sai / 10 phút cho mỗi email (chặn dò từ nhiều IP). Khoá theo email+IP
        // chứ không chỉ IP vì cả lớp học có thể dùng chung một IP.
        $email   = Str::lower(trim($credentials['email']));
        $keyIp   = 'login:' . $email . '|' . $request->ip();
        $keyMail = 'login-email:' . $email;

        if (RateLimiter::tooManyAttempts($keyIp, 5) || RateLimiter::tooManyAttempts($keyMail, 20)) {
            $seconds = max(RateLimiter::availableIn($keyIp), RateLimiter::availableIn($keyMail));
            $wait    = $seconds >= 60 ? ceil($seconds / 60) . ' phút' : $seconds . ' giây';

            return back()->withErrors([
                'email' => "Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau {$wait}.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($keyIp);
            $request->session()->regenerate();
            
            return redirect()->intended(route('dashboard'));
        }

        RateLimiter::hit($keyIp, 60);
        RateLimiter::hit($keyMail, 600);

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        $zaloSetting = \App\Models\Setting::where('key', 'zalo_contact_number')->first();
        $zaloNumber = $zaloSetting ? $zaloSetting->value : '0886160515';
        
        return redirect()->away('https://zalo.me/' . $zaloNumber);
    }

    public function register(Request $request)
    {
        $zaloSetting = \App\Models\Setting::where('key', 'zalo_contact_number')->first();
        $zaloNumber = $zaloSetting ? $zaloSetting->value : '0886160515';
        
        return redirect()->away('https://zalo.me/' . $zaloNumber);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $deviceId = $request->cookie('aptis_device_id');
            
            if ($deviceId) {
                \App\Models\LoginSession::where('user_id', $user->id)
                    ->where('device_id', $deviceId)
                    ->delete();
            }
            
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
