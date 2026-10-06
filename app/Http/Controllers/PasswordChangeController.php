<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordChangeController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // Bị ép đổi lần đầu (mật khẩu mặc định sau thanh toán) thì không hỏi mật khẩu
        // cũ. Tự đổi trong lúc dùng bình thường thì phải nhập đúng mật khẩu hiện tại,
        // tránh người khác cầm máy đang đăng nhập đổi mất mật khẩu.
        $rules = ['password' => ['required', 'confirmed', Password::min(8)]];
        if (! $user->must_change_password) {
            $rules['current_password'] = ['required', 'current_password'];
            $rules['password'][] = 'different:current_password';
        }

        $data = $request->validate($rules, [
            'current_password.required'         => 'Vui lòng nhập mật khẩu hiện tại.',
            'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.different'                => 'Mật khẩu mới phải khác mật khẩu hiện tại.',
        ]);

        $user->update([
            'password'             => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('success', 'Đã đổi mật khẩu thành công!');
    }
}
