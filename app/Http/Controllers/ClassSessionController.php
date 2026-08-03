<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use Illuminate\Http\Request;

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
        $sessions = ClassSession::visibleToStudents()
            ->allowedFor(auth()->user())
            ->with('classGroup:id,name')
            ->get();

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

        // Tư cách thành viên. Danh sách ở `index` đã lọc, nhưng KHÔNG được tin vào
        // việc đó: đây là chỗ duy nhất trả link Meet ra ngoài, và ai cũng gõ thẳng
        // được `/lop-hoc/9/join`. Cùng một luật, kiểm tra lại lần nữa tại cổng.
        if (! $user->canJoinClassSession($classSession)) {
            return redirect()->route('classes.index')
                ->with('error', 'Buổi học này không thuộc lớp của bạn.');
        }

        if (!$classSession->isJoinable()) {
            $message = $classSession->hasEnded() || !$classSession->is_active
                ? 'Buổi học này đã kết thúc.'
                : (! $classSession->hasMeetLink()
                    ? 'Buổi học chưa có link phòng. Vui lòng báo giảng viên.'
                    : 'Chưa tới giờ vào lớp. Cửa lớp mở trước giờ học '
                        . ClassSession::JOIN_EARLY_MINUTES . ' phút.');

            return redirect()->route('classes.index')->with('error', $message);
        }

        // Ghi nhật ký TRƯỚC khi trả link. Nội quy hiển thị cho học viên nói
        // "mỗi lần vào lớp đều được ghi lại" — phải ghi thật thì lời đó mới đúng.
        \App\Models\ClassSessionJoin::create([
            'user_id'          => $user->id,
            'class_session_id' => $classSession->id,
            'ip_address'       => request()->ip(),
            'user_agent'       => substr((string) request()->userAgent(), 0, 512),
        ]);

        return redirect()->away($classSession->effectiveMeetLink());
    }

    /**
     * Học viên tự khai Gmail dùng để vào lớp. Giảng viên mời đúng các địa chỉ
     * này qua Google Calendar → họ vào thẳng, người ngoài phải xin duyệt.
     */
    public function saveGoogleEmail(Request $request)
    {
        $data = $request->validate([
            'google_email' => 'nullable|email|max:255',
        ], [
            'google_email.email' => 'Địa chỉ Gmail không hợp lệ.',
        ]);

        // Bắt gõ nhầm tên miền (gmai.com, gmail.con…). Những địa chỉ này ĐÚNG cú
        // pháp nên `email` cho qua, nhưng không tồn tại — để lọt thì học viên sẽ
        // không bao giờ vào thẳng được mà chẳng ai hiểu vì sao.
        if ($data['google_email'] && $goiY = \App\Support\InviteEmail::gmailTypoSuggestion($data['google_email'])) {
            return back()
                ->withInput()
                ->withErrors(['google_email' => "Địa chỉ này có vẻ gõ nhầm. Có phải bạn định nhập {$goiY} không?"]);
        }

        auth()->user()->update(['google_email' => $data['google_email'] ?: null]);

        return redirect()->route('classes.index')->with(
            'success',
            $data['google_email']
                ? 'Đã lưu Gmail vào lớp. Giảng viên sẽ mời địa chỉ này vào buổi học.'
                : 'Đã xoá Gmail vào lớp.'
        );
    }
}
