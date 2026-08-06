<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Quản lý LỚP học online: thông tin lớp + danh sách thành viên.
 *
 * Thành viên là danh sách tường minh, không phải kết quả của bộ lọc. Bộ lọc theo
 * `source` chỉ để admin chọn nhanh (2 cú bấm cho cả trăm người) rồi vẫn sửa được
 * từng người — xem chú thích trong migration `create_class_groups_table`.
 */
class ClassGroupController extends Controller
{
    public function index()
    {
        $groups = ClassGroup::withCount(['members', 'sessions'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.class-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.class-groups.create');
    }

    public function store(Request $request)
    {
        $group = ClassGroup::create($this->validated($request));

        return redirect()->route('admin.class-groups.members', $group)
            ->with('success', 'Đã tạo lớp. Giờ chọn thành viên cho lớp.');
    }

    public function edit(ClassGroup $classGroup)
    {
        return view('admin.class-groups.edit', compact('classGroup'));
    }

    public function update(Request $request, ClassGroup $classGroup)
    {
        $classGroup->update($this->validated($request));

        return redirect()->route('admin.class-groups.index')
            ->with('success', 'Đã cập nhật lớp.');
    }

    /**
     * Khoá ngoại đặt `restrictOnDelete` nên DB sẽ ném lỗi nếu lớp còn buổi học.
     * Bắt trước ở đây để admin đọc được câu tiếng Việt thay vì trang lỗi SQL.
     */
    public function destroy(ClassGroup $classGroup)
    {
        if ($classGroup->sessions()->exists()) {
            return back()->with('error',
                'Lớp này còn buổi học. Xoá hoặc chuyển các buổi sang lớp khác trước đã.');
        }

        $classGroup->delete();

        return redirect()->route('admin.class-groups.index')->with('success', 'Đã xoá lớp.');
    }

    /**
     * Màn chọn thành viên: bên trên là người đã trong lớp, bên dưới là ứng viên
     * có lọc theo nguồn + tìm theo tên/email.
     */
    public function members(Request $request, ClassGroup $classGroup)
    {
        $classGroup->load('members');

        // Bảng thành viên phân trang RIÊNG (tên trang `tv`) để lật trang ở khung
        // này không kéo theo khung ứng viên bên dưới nhảy trang.
        //
        // ⚠️ Danh sách mời Calendar vẫn dựng từ `$classGroup->members` ĐẦY ĐỦ,
        // không phải từ trang đang xem. Nút copy mà chỉ lấy 25 địa chỉ của một
        // trang trong khi lớp có 710 người là hỏng IM LẶNG: admin dán vào
        // Calendar, thấy có email nên tin là xong, và chỉ phát hiện khi 685 học
        // viên phải xin duyệt giữa buổi dạy.
        $timTV = trim((string) $request->input('qtv'));

        $thanhVien = $classGroup->members()
            ->when($timTV !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$timTV}%")
                ->orWhere('email', 'like', "%{$timTV}%")))
            ->paginate(25, ['*'], 'tv')
            ->withQueryString();

        // Mặc định lọc theo ý định của lớp; admin đổi được ngay trên màn.
        $source = $request->input('source', $classGroup->source_filter);
        $tuKhoa = trim((string) $request->input('q'));
        $sapThi = (int) $request->input('sap_thi', 0);

        $ungVien = $this->locUngVien($request, $classGroup)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        // Số ứng viên khớp bộ lọc HIỆN TẠI — dùng cho nút "thêm tất cả". Phải là
        // tổng của cả bộ lọc chứ không phải của trang đang xem, nếu không admin
        // bấm "thêm tất cả 25" mà thực tế vào 400 người.
        $tongUngVien = $ungVien->total();

        return view('admin.class-groups.members', compact(
            'classGroup', 'thanhVien', 'timTV', 'ungVien', 'source', 'tuKhoa', 'sapThi', 'tongUngVien'
        ));
    }

    public function addMembers(Request $request, ClassGroup $classGroup)
    {
        $data = $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
        ], [
            'user_ids.required' => 'Chưa chọn học viên nào.',
        ]);

        // Không bao giờ thêm admin vào lớp học viên.
        $ids = User::whereIn('id', $data['user_ids'])->where('role', '!=', 'admin')->pluck('id');

        // `syncWithoutDetaching` để bấm hai lần không sinh lỗi trùng khoá.
        $classGroup->members()->syncWithoutDetaching(
            $ids->mapWithKeys(fn ($id) => [$id => ['added_at' => now()]])->all()
        );

        return back()->with('success', "Đã thêm {$ids->count()} học viên vào lớp.");
    }

    /**
     * Thêm TẤT CẢ học viên khớp bộ lọc đang xem — đây là nút giúp admin không phải
     * tick từng người trong 800 tài khoản.
     *
     * Chủ ý nhận lại `source`/`q` từ form chứ không đọc từ session: nút phải làm
     * đúng cái bộ lọc admin đang NHÌN THẤY, không phải bộ lọc lần trước.
     */
    /**
     * NGUỒN SỰ THẬT DUY NHẤT cho "ứng viên khớp bộ lọc hiện tại".
     *
     * ⚠️ Màn hiển thị và nút "thêm tất cả kết quả lọc" PHẢI đọc cùng một truy vấn.
     * Trước đây hai chỗ chép logic của nhau và đã lệch thật: `addAllMatching`
     * không lấy `source` mặc định của lớp, nên với lớp có `source_filter`, màn
     * hiển thị lọc còn nút "thêm tất cả" thì thêm CẢ TRƯỜNG. Thêm một bộ lọc mới
     * mà quên một chỗ là lỗi hoàn toàn im lặng — admin bấm, thấy báo thành công,
     * và chỉ phát hiện khi có người lạ vào lớp.
     *
     * Cũng loại sẵn người đã trong lớp, để con số báo về là "số người MỚI thêm".
     */
    private function locUngVien(Request $request, ClassGroup $classGroup): \Illuminate\Database\Eloquent\Builder
    {
        $source = $request->input('source', $classGroup->source_filter);
        $tuKhoa = trim((string) $request->input('q'));
        $sapThi = (int) $request->input('sap_thi', 0);

        return User::query()
            ->where('role', '!=', 'admin')
            ->ofSource($source ?: null)
            ->whereNotIn('id', $classGroup->members()->pluck('users.id'))
            ->when($tuKhoa !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$tuKhoa}%")
                ->orWhere('email', 'like', "%{$tuKhoa}%")))
            // "Sắp thi trong N ngày" — đọc `expires_at`, vì ô "Ngày thi (Exam
            // Date)" ở form tạo user ghi vào chính cột đó.
            ->when($sapThi > 0, fn ($q) => $q
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now()->startOfDay(), now()->addDays($sapThi)->endOfDay()]));
    }

    public function addAllMatching(Request $request, ClassGroup $classGroup)
    {
        $ids = $this->locUngVien($request, $classGroup)->pluck('id');

        $classGroup->members()->syncWithoutDetaching(
            $ids->mapWithKeys(fn ($id) => [$id => ['added_at' => now()]])->all()
        );

        return back()->with('success', "Đã thêm {$ids->count()} học viên khớp bộ lọc vào lớp.");
    }

    public function removeMember(ClassGroup $classGroup, User $user)
    {
        $classGroup->members()->detach($user->id);

        return back()->with('success', "Đã gỡ {$user->name} khỏi lớp.");
    }

    private function validated(Request $request): array
    {
        // Nhận cả `meet.google.com/abc-defg-hij` lẫn mỗi mã phòng — xem `MeetLink`.
        $request->merge(['meet_link' => \App\Support\MeetLink::normalize($request->input('meet_link'))]);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'source_filter' => 'nullable|in:' . implode(',', array_keys(User::SOURCE_LABELS)),
            // Trần 60 ngày: quá đó thì "sắp thi" mất nghĩa và lớp gom gần hết
            // trường — mà admin sẽ không nhận ra vì con số vẫn tăng dần.
            'auto_exam_days' => 'nullable|integer|min:1|max:60',
            // Để `url` chung (không ép meet.google.com) phòng khi đổi nền tảng.
            'meet_link'     => 'nullable|url|max:500',
            'is_active'     => 'boolean',
        ], [
            'meet_link.url' => 'Link phòng học không hợp lệ. Dán nguyên link Meet (hoặc chỉ mã phòng dạng abc-defg-hij) là được.',
        ]);

        // `?? null` bắt buộc: `validate()` KHÔNG trả về key nào vắng mặt trong
        // request. Form đầy đủ thì luôn có, nhưng chỉ cần một ô bị bỏ khỏi form
        // là "Undefined array key" → lỗi 500. Đã dính thật khi test.
        $data['source_filter']  = ($data['source_filter'] ?? null) ?: null;
        $data['meet_link']      = ($data['meet_link'] ?? null) ?: null;
        // Ô trống = lớp thường (thành viên chọn tay), không phải "tự gom 0 ngày".
        $data['auto_exam_days'] = ($data['auto_exam_days'] ?? null) ?: null;

        return $data;
    }
}
