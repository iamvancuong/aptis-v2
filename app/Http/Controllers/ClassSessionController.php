<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;

/**
 * Cổng vào lớp online cho học viên.
 *
 * Toàn bộ việc chặn "học chui" nằm ở tầng web này (KHÔNG phải ở Google Meet):
 * phải đăng nhập (middleware auth) + tài khoản còn hạn + buổi đang mở cửa.
 * Chỉ khi đủ điều kiện mới redirect sang link Meet.
 */
class ClassSessionController extends Controller
{
    public function index()
    {
        $sessions = ClassSession::visibleToStudents()->get();

        return view('class-sessions.index', compact('sessions'));
    }

    public function join(ClassSession $classSession)
    {
        $user = auth()->user();

        // Lưới an toàn: middleware CheckAccountExpiration (áp cho mọi route web)
        // đã logout người hết hạn trước khi tới đây, nên nhánh này gần như không
        // chạy. Giữ lại vì đây là chỗ duy nhất trả link Meet ra ngoài — nếu thứ
        // tự middleware đổi về sau, link vẫn không rò cho tài khoản hết hạn.
        if ($user->isExpired()) {
            return redirect()->route('classes.index')
                ->with('error', 'Tài khoản của bạn đã hết hạn. Vui lòng gia hạn để vào lớp.');
        }

        if (!$classSession->isJoinable()) {
            $message = $classSession->hasEnded() || !$classSession->is_active
                ? 'Buổi học này đã kết thúc.'
                : 'Chưa tới giờ vào lớp. Cửa lớp mở trước giờ học '
                    . ClassSession::JOIN_EARLY_MINUTES . ' phút.';

            return redirect()->route('classes.index')->with('error', $message);
        }

        return redirect()->away($classSession->meet_link);
    }
}
