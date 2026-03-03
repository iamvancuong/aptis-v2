<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('dashboard'));
        }

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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
