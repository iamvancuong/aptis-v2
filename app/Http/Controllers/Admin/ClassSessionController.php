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
        $sessions = ClassSession::orderByDesc('starts_at')->paginate(15);

        return view('admin.class-sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('admin.class-sessions.create');
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
