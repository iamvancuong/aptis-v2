<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use Illuminate\Http\Request;

/**
 * Quản lý buổi học online (Pha 0): admin tự tạo phòng trên Google Meet rồi dán
 * link vào đây kèm khung giờ. Không gọi API Google — xem §16 trong TIEN_DO.md.
 */
class ClassSessionController extends Controller
{
    public function index()
    {
        $sessions = ClassSession::withCount('joins')->orderByDesc('starts_at')->paginate(15);

        // Danh sách mời qua Google Calendar: mọi học viên còn hạn. Mặc định lấy
        // email tài khoản; ai đã khai Gmail riêng thì lấy Gmail đó.
        $hocVien = \App\Models\User::invitableToClass()->get(['id', 'name', 'email', 'google_email']);

        // Tách địa chỉ gõ nhầm tên miền Gmail (gmai.com, gmail.con…) ra khỏi danh
        // sách mời: chúng KHÔNG TỒN TẠI nên mời vào là mời hư không, mà học viên
        // đó buổi nào cũng phải xin duyệt và không ai hiểu vì sao.
        $emailHong = $hocVien
            ->map(fn ($u) => [
                'user'   => $u,
                'email'  => $u->classInviteEmail(),
                'goi_y'  => \App\Support\InviteEmail::gmailTypoSuggestion($u->classInviteEmail()),
            ])
            ->reject(fn ($r) => \App\Support\InviteEmail::isUsable($r['email']))
            ->values();

        $guestEmails = $hocVien
            ->map->classInviteEmail()
            ->filter(fn ($e) => \App\Support\InviteEmail::isUsable($e))
            ->unique()
            ->values()
            ->all();

        // Địa chỉ hợp lệ nhưng không phải @gmail.com — mời vẫn được, chỉ là có thể
        // không gắn với tài khoản Google nên người đó vẫn phải xin duyệt.
        $nonGmailCount = collect($guestEmails)
            ->reject(fn ($e) => str_ends_with(strtolower($e), '@gmail.com'))
            ->count();

        // Danh sách mời là ẢNH CHỤP tại thời điểm copy. Với sự kiện Calendar lặp
        // lại, người hết hạn tuần sau vẫn nằm trong lời mời cũ → nhắc admin.
        $sapHetHan = \App\Models\User::invitableToClass()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now()->addDays(7))
            ->count();

        return view('admin.class-sessions.index', compact(
            'sessions', 'guestEmails', 'nonGmailCount', 'emailHong', 'sapHetHan'
        ));
    }

    public function create()
    {
        return view('admin.class-sessions.create');
    }

    /**
     * Nhật ký vào lớp của một buổi. Dấu hiệu chia sẻ link đáng ngờ nhất là
     * MỘT tài khoản vào từ NHIỀU địa chỉ mạng khác nhau trong cùng buổi.
     */
    public function joins(ClassSession $classSession)
    {
        $joins = $classSession->joins()
            ->with('user:id,name,email')
            ->latest('id')
            ->paginate(50);

        // Đếm số IP khác nhau mỗi học viên đã dùng trong buổi này (1 truy vấn).
        $ipCounts = $classSession->joins()
            ->selectRaw('user_id, COUNT(DISTINCT ip_address) as ips, COUNT(*) as lan')
            ->groupBy('user_id')
            ->pluck('ips', 'user_id');

        return view('admin.class-sessions.joins', compact('classSession', 'joins', 'ipCounts'));
    }

    public function store(Request $request)
    {
        ClassSession::create($this->validated($request));

        return redirect()->route('admin.class-sessions.index')
            ->with('success', 'Đã tạo buổi học.');
    }

    public function edit(ClassSession $classSession)
    {
        return view('admin.class-sessions.edit', compact('classSession'));
    }

    public function update(Request $request, ClassSession $classSession)
    {
        $classSession->update($this->validated($request));

        return redirect()->route('admin.class-sessions.index')
            ->with('success', 'Đã cập nhật buổi học.');
    }

    public function destroy(ClassSession $classSession)
    {
        $classSession->delete();

        return redirect()->route('admin.class-sessions.index')
            ->with('success', 'Đã xoá buổi học.');
    }

    private function validated(Request $request): array
    {
        // Giờ để trống được (giảm thao tác cho giảng viên): trống = không giới hạn
        // phía đó. Chỉ so sánh trước/sau khi CẢ HAI cùng được điền — nếu không,
        // rule `after:starts_at` sẽ đem so với một giá trị rỗng.
        $bothTimesGiven = $request->filled('starts_at') && $request->filled('ends_at');

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            // Để url chung (không ép meet.google.com) phòng khi dùng nền tảng khác.
            'meet_link'   => 'required|url|max:500',
            'starts_at'   => 'nullable|date',
            'ends_at'     => 'nullable|date' . ($bothTimesGiven ? '|after:starts_at' : ''),
            'is_active'   => 'boolean',
        ], [
            'ends_at.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'meet_link.url' => 'Link phòng học phải là một URL hợp lệ (bắt đầu bằng https://).',
        ]);

        // Ô trống về DB là null (không phải chuỗi rỗng) để các hàm giờ hiểu đúng.
        $data['starts_at'] = $data['starts_at'] ?: null;
        $data['ends_at']   = $data['ends_at'] ?: null;

        return $data;
    }
}
