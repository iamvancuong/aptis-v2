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
    /** Mốc thời gian admin xác nhận đã cập nhật lời mời Calendar lần gần nhất. */
    private const INVITE_SYNC_KEY = 'class_invite_synced_at';

    /**
     * Admin bấm sau khi đã gỡ người hết hạn khỏi sự kiện Calendar. Ghi mốc để
     * lần sau chỉ hiện người hết hạn TỪ đó — danh sách luôn là việc còn phải làm.
     */
    public function markInviteSynced()
    {
        \App\Models\Setting::updateOrCreate(
            ['key' => self::INVITE_SYNC_KEY],
            ['value' => now()->toDateTimeString(), 'label' => 'Lần cuối cập nhật lời mời lớp online']
        );

        return redirect()->route('admin.class-sessions.index')
            ->with('success', 'Đã ghi nhận. Danh sách cần gỡ sẽ tính lại từ bây giờ.');
    }

    public function index()
    {
        $sessions = ClassSession::with('classGroup:id,name')
            ->withCount(['joins', 'extraMembers'])
            ->orderByDesc('starts_at')
            ->paginate(15);

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

        // ⚠️ Lỗ hổng thật: học viên hết hạn NHƯNG vẫn nằm trong lời mời Calendar
        // thì có link trong lịch của họ và vào Meet thẳng — KHÔNG đi qua cổng web
        // nên hệ thống không chặn được. Phải gỡ tay khỏi sự kiện Calendar.
        // Chỉ liệt kê người hết hạn KỂ TỪ lần admin bấm "Đã cập nhật lời mời",
        // để danh sách luôn là việc còn phải làm, không lặp lại việc đã xong.
        $lanDongBo = \App\Models\Setting::where('key', self::INVITE_SYNC_KEY)->value('value');
        $moc = $lanDongBo ? \Illuminate\Support\Carbon::parse($lanDongBo) : now()->subDays(60);

        $canGoBo = \App\Models\User::where('role', '!=', 'admin')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->where('expires_at', '>=', $moc)
            ->orderByDesc('expires_at')
            ->get(['id', 'name', 'email', 'google_email', 'expires_at']);

        // Từ khi có lớp, danh sách mời TOÀN TRƯỜNG ở trang này thành cái bẫy:
        // dán nó vào sự kiện Calendar của lớp "trừ nhóm web" là mời luôn cả nhóm
        // web vào — phá đúng thứ việc chia lớp dựng lên, và không báo lỗi gì.
        // Có lớp thì phải đẩy admin sang danh sách RIÊNG của từng lớp.
        $lopHoc = \App\Models\ClassGroup::withCount('members')->orderBy('name')->get(['id', 'name']);

        return view('admin.class-sessions.index', compact(
            'sessions', 'guestEmails', 'nonGmailCount', 'emailHong', 'sapHetHan', 'canGoBo', 'lanDongBo', 'lopHoc'
        ));
    }

    public function create()
    {
        return view('admin.class-sessions.create', [
            'groups'     => \App\Models\ClassGroup::orderBy('name')->get(['id', 'name', 'meet_link', 'is_active']),
            'ungVien'    => $this->ungVienKhachMoi(),
        ]);
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
        $session = ClassSession::create($this->validated($request));
        $this->syncExtraMembers($request, $session);

        return $this->veDanhSach($session, 'Đã tạo buổi học.');
    }

    public function edit(ClassSession $classSession)
    {
        $classSession->load('extraMembers:id,name,email');

        return view('admin.class-sessions.edit', [
            'classSession' => $classSession,
            'groups'       => \App\Models\ClassGroup::orderBy('name')->get(['id', 'name', 'meet_link', 'is_active']),
            'ungVien'      => $this->ungVienKhachMoi(),
        ]);
    }

    public function update(Request $request, ClassSession $classSession)
    {
        $classSession->update($this->validated($request));
        $this->syncExtraMembers($request, $classSession->refresh());

        return $this->veDanhSach($classSession, 'Đã cập nhật buổi học.');
    }

    /**
     * Lưu buổi thiếu link là chuyện bình thường (lên lịch trước, mở phòng sau),
     * nhưng phải NÓI RA. Không nói thì admin tưởng xong việc, tới giờ học viên
     * mở `/lop-hoc` không thấy nút và không ai hiểu vì sao.
     */
    private function veDanhSach(ClassSession $session, string $thongBao)
    {
        $redirect = redirect()->route('admin.class-sessions.index');

        if (! $session->load('classGroup')->hasMeetLink()) {
            return $redirect->with('warning', $thongBao
                . ' ⚠️ Buổi này CHƯA CÓ link phòng nên học viên chưa vào được —'
                . ' dán link vào buổi, hoặc vào lớp dán link dùng chung cho cả lớp.');
        }

        return $redirect->with('success', $thongBao);
    }

    /**
     * Khách được mời thêm cho riêng buổi này (học thử, học bù).
     *
     * Buổi KHÔNG gắn lớp thì mọi học viên còn hạn đã vào được rồi — giữ lại danh
     * sách khách lúc đó chỉ tạo ảo giác là nó đang hạn chế ai đó. Xoá sạch cho
     * khỏi hiểu nhầm.
     */
    /**
     * Ứng viên cho ô "khách mời thêm". Chỉ học viên còn hạn — mời người đã hết
     * hạn là mời vào một cánh cửa mà `canJoinClassSession` vẫn đóng.
     */
    private function ungVienKhachMoi(): \Illuminate\Support\Collection
    {
        return \App\Models\User::invitableToClass()->get(['id', 'name', 'email']);
    }

    private function syncExtraMembers(Request $request, ClassSession $session): void
    {
        if ($session->class_group_id === null) {
            $session->extraMembers()->sync([]);
            return;
        }

        $ids = \App\Models\User::whereIn('id', (array) $request->input('extra_user_ids', []))
            ->where('role', '!=', 'admin')
            ->pluck('id')
            ->all();

        $session->extraMembers()->sync($ids);
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

        // Nhận cả `meet.google.com/abc-defg-hij` lẫn mỗi mã phòng — xem `MeetLink`.
        $request->merge(['meet_link' => \App\Support\MeetLink::normalize($request->input('meet_link'))]);

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'class_group_id' => 'nullable|exists:class_groups,id',
            // KHÔNG bắt buộc: quy trình thật là lên lịch buổi trước, mở phòng Meet
            // sau. Buổi thiếu link thì `isJoinable()` trả false nên học viên không
            // thấy nút, màn admin tô đỏ "Chưa có link", và controller nhắc một câu
            // lúc lưu. Chặn ngay lúc nhập chỉ ép admin dán link giả cho qua cửa.
            // Để `url` chung (không ép meet.google.com) phòng khi đổi nền tảng.
            'meet_link'      => 'nullable|url|max:500',
            // Lặp hằng tuần mà không có giờ bắt đầu thì không suy ra được thứ mấy
            // để lặp. Chặn ngay ở đây thay vì để lệnh sinh buổi im lặng bỏ qua —
            // admin tick xong tưởng đã xong việc, tuần sau không có buổi nào.
            'starts_at'      => 'nullable|date|required_if:repeat_weekly,1',
            'ends_at'        => 'nullable|date' . ($bothTimesGiven ? '|after:starts_at' : ''),
            'is_active'      => 'boolean',
            'repeat_weekly'  => 'boolean',
        ], [
            'ends_at.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'starts_at.required_if' => 'Muốn lặp hằng tuần thì phải điền giờ bắt đầu (để biết lặp vào thứ mấy, mấy giờ).',
            'meet_link.url' => 'Link phòng học không hợp lệ. Dán nguyên link Meet (hoặc chỉ mã phòng dạng abc-defg-hij) là được.',
        ]);

        // Ô trống về DB là null (không phải chuỗi rỗng) để các hàm giờ hiểu đúng.
        // Dùng `?? null` chứ không phải `$data['x'] ?: null`: `validate()` KHÔNG trả
        // về key nào không có trong request, nên trường bỏ trống hoàn toàn (form
        // khác, hoặc gọi qua API) sẽ ném "Undefined array key" chứ không âm thầm.
        $data['starts_at']      = ($data['starts_at'] ?? null) ?: null;
        $data['ends_at']        = ($data['ends_at'] ?? null) ?: null;
        $data['meet_link']      = ($data['meet_link'] ?? null) ?: null;
        $data['class_group_id'] = ($data['class_group_id'] ?? null) ?: null;

        return $data;
    }
}
