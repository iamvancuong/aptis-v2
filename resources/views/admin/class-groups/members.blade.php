@extends('layouts.admin')

@section('title', 'Thành viên lớp - Admin')
@section('header', 'Thành viên: ' . $classGroup->name)

@section('content')
@php
    $inviteEmails = $classGroup->inviteEmails();
    $canGoBo      = $classGroup->membersToRemoveFromInvite();
@endphp

<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.class-groups.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Quay lại danh sách lớp
    </a>
    <a href="{{ route('admin.class-groups.edit', $classGroup) }}" class="text-sm text-gray-600 hover:text-gray-800">Sửa thông tin lớp</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-200 rounded-lg text-sm text-green-800">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">{{ $errors->first() }}</div>
@endif

{{-- ① Danh sách mời Calendar CỦA RIÊNG LỚP NÀY. Khác với màn buổi học (mời cả
     trường) — mỗi lớp một phòng thì mỗi lớp một danh sách. --}}
<x-card class="mb-6">
    <div x-data="{ copied: false, list: @js(implode(', ', $inviteEmails)) }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Danh sách mời của lớp (Google Calendar)</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong>{{ count($inviteEmails) }}</strong> địa chỉ dùng được (đã bỏ người hết hạn, bị khoá và email gõ sai).
                    Dán vào ô <strong>Khách mời</strong> của sự kiện Calendar lặp lại của lớp.
                </p>
            </div>
            @if(count($inviteEmails) > 0)
                <button type="button"
                        @click="navigator.clipboard.writeText(list); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
                    <span x-show="!copied">Copy {{ count($inviteEmails) }} địa chỉ</span>
                    <span x-show="copied" x-cloak>✓ Đã copy</span>
                </button>
            @endif
        </div>

        @if(count($inviteEmails) > 0)
            <textarea readonly rows="2" x-text="list"
                      class="w-full px-3 py-2 text-xs font-mono border border-gray-200 rounded-lg bg-gray-50 text-gray-600"></textarea>
        @else
            <p class="text-sm text-gray-500 italic">Lớp chưa có thành viên nào còn hạn.</p>
        @endif
    </div>

    @if($canGoBo->isNotEmpty())
        {{-- Lỗ hổng §25②: người hết hạn còn lời mời thì mở Google Calendar là có
             link, vào thẳng Meet mà KHÔNG đi qua cổng web. Google không tự gỡ. --}}
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg"
             x-data="{ copied: false, list: @js($canGoBo->map->classInviteEmail()->implode(', ')) }">
            <p class="text-xs font-semibold text-red-800 mb-2">
                {{ $canGoBo->count() }} thành viên đã hết hạn / bị khoá — PHẢI gỡ khỏi lời mời Calendar
            </p>
            <p class="text-xs text-red-700 mb-2">
                Họ vẫn còn lời mời trong lịch, nghĩa là vẫn có link và <strong>vào thẳng phòng được mà không
                đi qua web</strong> — hệ thống không chặn được khâu này. Gỡ khỏi ô Khách mời của sự kiện Calendar,
                rồi gỡ họ khỏi lớp ở bảng dưới.
            </p>
            <button type="button"
                    @click="navigator.clipboard.writeText(list); copied = true; setTimeout(() => copied = false, 2000)"
                    class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                <span x-show="!copied">Copy {{ $canGoBo->count() }} địa chỉ cần gỡ</span>
                <span x-show="copied" x-cloak>✓ Đã copy</span>
            </button>
        </div>
    @endif
</x-card>

{{-- ② Thành viên hiện tại.
     Phân trang + ô tìm vì lớp thật đang có 710 người: không có hai thứ này thì
     gỡ một người khỏi lớp nghĩa là cuộn qua 710 dòng. Số trong tiêu đề vẫn là
     TỔNG của cả lớp, không phải số dòng đang hiện — đó là con số admin đối
     chiếu với danh sách mời Calendar. --}}
<x-card class="mb-6" title="Thành viên hiện tại ({{ $classGroup->members->count() }})">
    @if($classGroup->members->isEmpty())
        <p class="text-sm text-gray-500 italic py-2">Chưa có ai. Chọn học viên ở khung bên dưới.</p>
    @else
        <form method="GET" class="mb-3 flex gap-2">
            @if($source)<input type="hidden" name="source" value="{{ $source }}">@endif
            @if($tuKhoa)<input type="hidden" name="q" value="{{ $tuKhoa }}">@endif
            {{-- Class dùng lại y hệt ô lọc ứng viên bên dưới — cố ý. Class Tailwind
                 mới không có trong `public/build` thì trên production ô này mất
                 style mà không báo lỗi gì (bẫy §25). Bản đầu dùng nút xám
                 `hover:bg-gray-800` — đã đối chiếu và nó THIẾU thật.

                 ⚠️ Cách đối chiếu: Tailwind ESCAPE dấu hai chấm trong file CSS, nên
                 phải tìm `hover\:bg-gray-800`, không phải `hover:bg-gray-800`. Tìm
                 sai kiểu thì class nào cũng báo "thiếu" và ta sẽ đi build lại vô ích:
                     grep -F 'hover\:bg-blue-700' public/build/assets/*.css --}}
            <input type="text" name="qtv" value="{{ $timTV }}"
                   placeholder="Tìm trong lớp theo tên hoặc email…"
                   class="flex-1 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">Tìm</button>
            @if($timTV !== '')
                <a href="{{ route('admin.class-groups.members', $classGroup) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Xoá lọc</a>
            @endif
        </form>

        @if($thanhVien->isEmpty())
            <p class="text-sm text-gray-500 italic py-2">Không có thành viên nào khớp “{{ $timTV }}”.</p>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-200">
                        <th class="py-2 pr-4">Học viên</th>
                        <th class="py-2 pr-4">Email vào lớp</th>
                        <th class="py-2 pr-4">Nguồn</th>
                        <th class="py-2 pr-4">Hạn</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($thanhVien as $m)
                        <tr>
                            <td class="py-2 pr-4 font-medium text-gray-800">{{ $m->name }}</td>
                            <td class="py-2 pr-4 text-gray-600 font-mono text-xs">{{ $m->classInviteEmail() }}</td>
                            <td class="py-2 pr-4"><x-badge>{{ $m->sourceLabel() }}</x-badge></td>
                            <td class="py-2 pr-4">
                                @if($m->isBlocked())
                                    <x-badge variant="danger">Bị khoá</x-badge>
                                @elseif($m->isExpired())
                                    <x-badge variant="danger">Hết hạn</x-badge>
                                @elseif($m->expires_at)
                                    <span class="text-xs text-gray-600">{{ $m->expires_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-xs text-gray-500">Không hạn</span>
                                @endif
                            </td>
                            <td class="py-2 text-right">
                                <form action="{{ route('admin.class-groups.members.remove', [$classGroup, $m]) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-700 text-xs">Gỡ khỏi lớp</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $thanhVien->links() }}</div>
        @endif
    @endif
</x-card>

{{-- Lớp tự gom: danh sách do MÁY quản. Không nói ra thì admin ngồi chọn tay cả
     buổi rồi sáng hôm sau lệnh đồng bộ gỡ sạch — công cốc mà không hiểu vì sao. --}}
@if($classGroup->isAutoExamGroup())
    <div class="mb-6 p-3 bg-amber-50 border border-amber-200 rounded-lg">
        <p class="text-sm font-semibold text-amber-900 mb-1">
            Lớp này đang TỰ GOM theo ngày thi ({{ $classGroup->auto_exam_days }} ngày tới)
        </p>
        <p class="text-xs text-amber-800">
            Danh sách thành viên do lệnh <code>classes:sync-exam-groups</code> quản, chạy mỗi đêm 03:00.
            <strong>Người bạn thêm tay ở đây sẽ bị gỡ ở lần cập nhật sau</strong>, và người đã qua ngày thi
            tự rời lớp. Muốn tự chọn thành viên thì
            <a href="{{ route('admin.class-groups.edit', $classGroup) }}" class="underline font-medium">sửa lớp</a>
            và xoá trống ô “Tự gom học viên sắp thi”.
        </p>
    </div>
@endif

{{-- ③ Thêm thành viên: lọc rồi tick, hoặc thêm cả bộ lọc một phát --}}
<x-card title="Thêm học viên vào lớp">
    <form method="GET" action="{{ route('admin.class-groups.members', $classGroup) }}"
          class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="sm:w-64">
            <x-select name="source">
                <option value="">Mọi nguồn tài khoản</option>
                @foreach(\App\Models\User::SOURCE_LABELS as $key => $label)
                    <option value="{{ $key }}" {{ $source === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </x-select>
        </div>
        {{-- "Sắp thi trong N ngày" — đọc `expires_at`, vì ô "Ngày thi (Exam Date)"
             ở form tạo user ghi vào chính cột đó. Đây là bộ lọc để dựng lớp kiểu
             "Nhóm thi tuần này" bằng tay trong vài cú bấm.

             📌 `sm:w-64` KHÔNG có trong `public/build` (ô "Nguồn" bên cạnh cũng
             dùng đúng class này và cũng thiếu). Giữ nguyên cho hai ô như nhau —
             thực tế bề rộng do flex chia. Muốn nó có tác dụng thật thì phải chạy
             `npm run build` và upload lại `public/build`, không phải việc của
             riêng chỗ này. --}}
        <div class="sm:w-64">
            <x-select name="sap_thi">
                <option value="">Mọi ngày thi</option>
                <option value="3" {{ (string) $sapThi === '3' ? 'selected' : '' }}>Thi trong 3 ngày tới</option>
                <option value="7" {{ (string) $sapThi === '7' ? 'selected' : '' }}>Thi trong 7 ngày tới</option>
                <option value="14" {{ (string) $sapThi === '14' ? 'selected' : '' }}>Thi trong 14 ngày tới</option>
                <option value="30" {{ (string) $sapThi === '30' ? 'selected' : '' }}>Thi trong 30 ngày tới</option>
            </x-select>
        </div>
        <input type="text" name="q" value="{{ $tuKhoa }}" placeholder="Tìm theo tên hoặc email…"
               class="flex-1 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
            Lọc
        </button>
    </form>

    @if($tongUngVien > 0)
        {{-- Nút "thêm tất cả" mang theo đúng bộ lọc đang HIỆN trên màn, và ghi rõ
             số lượng thật của cả bộ lọc chứ không phải của trang đang xem. --}}
        <form action="{{ route('admin.class-groups.members.add-all', $classGroup) }}" method="POST"
              class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex flex-wrap items-center justify-between gap-3"
              onsubmit="return confirm('Thêm tất cả {{ $tongUngVien }} học viên khớp bộ lọc vào lớp?')">
            @csrf
            {{-- ⚠️ Mọi bộ lọc phải được mang theo đây. Thiếu một ô là nút này
                 thêm nhiều người hơn màn hình đang hiện — không báo lỗi gì. --}}
            <input type="hidden" name="source" value="{{ $source }}">
            <input type="hidden" name="q" value="{{ $tuKhoa }}">
            <input type="hidden" name="sap_thi" value="{{ $sapThi ?: '' }}">
            <p class="text-xs text-amber-800">
                Bộ lọc hiện tại khớp <strong>{{ $tongUngVien }}</strong> học viên chưa vào lớp.
            </p>
            <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition-colors">
                Thêm tất cả {{ $tongUngVien }} người
            </button>
        </form>
    @endif

    <form action="{{ route('admin.class-groups.members.add', $classGroup) }}" method="POST">
        @csrf
        @if($ungVien->isEmpty())
            <p class="text-sm text-gray-500 italic py-2">Không có học viên nào khớp bộ lọc (hoặc tất cả đã ở trong lớp).</p>
        @else
            <div class="overflow-x-auto" x-data="{ all: false }">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-200">
                            <th class="py-2 pr-4">
                                {{-- Chọn theo class chứ không theo selector tên trường:
                                     `name="user_ids[]"` có dấu ngoặc vuông, nhét vào
                                     querySelector trong thuộc tính HTML phải escape hai
                                     lớp và rất dễ hỏng âm thầm. --}}
                                <input type="checkbox" x-model="all"
                                       x-on:change="$el.closest('table').querySelectorAll('.js-ung-vien').forEach(c => c.checked = all)"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                            </th>
                            <th class="py-2 pr-4">Học viên</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4">Nguồn</th>
                            <th class="py-2 pr-4">Hạn</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ungVien as $u)
                            <tr>
                                <td class="py-2 pr-4">
                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}"
                                           class="js-ung-vien h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                                </td>
                                <td class="py-2 pr-4 font-medium text-gray-800">{{ $u->name }}</td>
                                <td class="py-2 pr-4 text-gray-600 font-mono text-xs">{{ $u->email }}</td>
                                <td class="py-2 pr-4"><x-badge>{{ $u->sourceLabel() }}</x-badge></td>
                                <td class="py-2 pr-4">
                                    @if($u->isExpired())
                                        <x-badge variant="danger">Hết hạn</x-badge>
                                    @elseif($u->expires_at)
                                        <span class="text-xs text-gray-600">{{ $u->expires_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-xs text-gray-500">Không hạn</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <div>{{ $ungVien->links() }}</div>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
                    Thêm những người đã tick
                </button>
            </div>
        @endif
    </form>
</x-card>

@endsection
