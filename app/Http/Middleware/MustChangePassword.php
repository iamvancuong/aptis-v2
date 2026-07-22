<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tài khoản tạo tự động sau thanh toán dùng mật khẩu mặc định. Middleware này
 * ép người dùng đổi mật khẩu ngay lần đăng nhập đầu, chặn mọi trang khác cho
 * tới khi đổi xong (trừ chính màn đổi mật khẩu và đăng xuất).
 */
class MustChangePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
