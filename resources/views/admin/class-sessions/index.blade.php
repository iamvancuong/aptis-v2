@extends('layouts.admin')

@section('title', 'Lớp online - Admin')
@section('header', 'Lớp học online')

@section('content')
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
