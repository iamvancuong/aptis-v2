@extends('layouts.admin')

@section('title', 'Lớp online - Admin')
@section('header', 'Lớp học online')

@section('content')

{{-- Danh sách mời qua Google Calendar. Đây là cách DUY NHẤT hiện có để người
     ngoài không vào thẳng được: mời đích danh học viên còn hạn, đặt phòng ở mức
     hạn chế. Không mời ai mà tắt "Truy cập nhanh" thì CẢ LỚP phải xin duyệt tay. --}}
<x-card class="mb-6">
    <div x-data="{ copied: false, list: @js(implode(', ', $guestEmails)) }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Danh sách mời vào lớp (Google Calendar)</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <strong>{{ count($guestEmails) }}</strong> học viên còn hạn.
                    @if($nonGmailCount > 0)
                        <span class="text-amber-700">{{ $nonGmailCount }} địa chỉ không phải @gmail.com — nếu ai đó không vào thẳng được, nhắc họ khai Gmail ở trang “Lớp học”.</span>
                    @endif
                </p>
            </div>
            @if(count($guestEmails) > 0)
                <button type="button"
                        @click="navigator.clipboard.writeText(list); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
                    <span x-show="!copied">Copy {{ count($guestEmails) }} địa chỉ</span>
                    <span x-show="copied" x-cloak>✓ Đã copy</span>
                </button>
            @endif
        </div>

        @if(count($guestEmails) > 0)
            <textarea readonly rows="2" x-text="list"
                      class="w-full px-3 py-2 text-xs font-mono border border-gray-200 rounded-lg bg-gray-50 text-gray-600"></textarea>
        @else
            <p class="text-sm text-gray-500 italic">Chưa học viên nào khai Gmail. Nhắc học viên điền ở trang “Lớp học”.</p>
        @endif

        <div class="mt-3 flex gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="text-xs text-blue-900">
                <p class="font-semibold mb-1">Cách dùng — làm 1 lần cho mỗi buổi</p>
                <ol class="list-decimal ml-4 space-y-0.5">
                    <li>Tạo sự kiện trên <strong>Google Calendar</strong>, bấm “Thêm Google Meet”.</li>
                    <li>Bấm <strong>Copy</strong> ở trên → dán vào ô <strong>Khách mời</strong>.</li>
                    <li>Copy link Meet của sự kiện → dán vào buổi học bên dưới.</li>
                    <li>Trong phòng: biểu tượng khiên → <strong>TẮT “Truy cập nhanh”</strong>.</li>
                </ol>
                <p class="mt-1.5">Kết quả: người được mời <strong>vào thẳng</strong>, người ngoài dù có link vẫn phải xin duyệt.</p>
            </div>
        </div>
    </div>
</x-card>

<x-card>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">Danh sách buổi học</h2>
            <p class="text-sm text-gray-500 mt-1">
                Tạo phòng trên Google Meet rồi dán link vào buổi học. Học viên còn hạn sẽ thấy nút “Vào lớp” trong khung giờ.
            </p>
        </div>
        <x-button :href="route('admin.class-sessions.create')" class="bg-blue-600 hover:bg-blue-700 text-white shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Thêm buổi học
        </x-button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buổi học</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link phòng</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sessions as $session)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $session->title }}</div>
                            @if($session->description)
                                <div class="text-sm text-gray-500 max-w-xs truncate" title="{{ $session->description }}">{{ $session->description }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @if($session->isAlwaysOpen())
                                <span class="text-gray-500 italic">Mở tự do</span>
                            @else
                                <div>{{ $session->starts_at?->format('d/m/Y H:i') ?? 'Mở ngay' }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $session->ends_at ? 'đến ' . $session->ends_at->format('H:i') : 'không tự đóng' }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $variant = match(true) {
                                    !$session->is_active => 'default',
                                    $session->hasEnded() => 'default',
                                    $session->isLive()   => 'success',
                                    default              => 'warning',
                                };
                            @endphp
                            <x-badge :variant="$variant">{{ $session->statusLabel() }}</x-badge>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ $session->meet_link }}" target="_blank" rel="noopener"
                               class="text-sm text-blue-600 hover:text-blue-800 underline max-w-[14rem] truncate inline-block align-bottom"
                               title="{{ $session->meet_link }}">{{ $session->meet_link }}</a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                <a href="{{ route('admin.class-sessions.edit', $session) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 p-1.5 rounded-md hover:bg-indigo-100 transition-colors" title="Chỉnh sửa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <form action="{{ route('admin.class-sessions.destroy', $session) }}" method="POST" class="inline-block" onsubmit="return confirm('Xoá buổi học này? Hành động không thể hoàn tác.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 p-1.5 rounded-md hover:bg-red-100 transition-colors" title="Xóa">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <p class="text-sm font-medium">Chưa có buổi học nào.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>
    @endif
</x-card>
@endsection
