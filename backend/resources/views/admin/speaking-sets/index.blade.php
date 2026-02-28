@extends('layouts.admin')

@section('title', 'Quản lý Bộ Đề Speaking')

@section('header', 'Quản lý Bộ Đề Speaking')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Speaking Sets</h1>
        <p class="text-gray-600 mt-1">Danh sách các bộ đề Speaking được cấu trúc đủ 4 Part.</p>
    </div>
    <x-button href="{{ route('admin.speaking-sets.create') }}">
        + Thêm Bộ Đề
    </x-button>
</div>

<x-datatable :data="$sets" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chủ đề (Title)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số câu hỏi (Parts)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($sets as $set)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ($sets->currentPage() - 1) * $sets->perPage() + $loop->iteration }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $set->title }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($set->questions_count == 4)
                        <x-badge variant="success">Đủ 4 Parts</x-badge>
                    @else
                        <x-badge variant="danger">Thiếu ({{ $set->questions_count }}/4)</x-badge>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($set->is_public)
                        <x-badge variant="success">Công khai</x-badge>
                    @else
                        <x-badge variant="default">Nháp</x-badge>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $set->created_at->format('d/m/Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                    <a href="{{ route('admin.speaking-sets.show', $set) }}" class="inline-flex items-center px-3 py-1 bg-sky-100 text-sky-700 rounded-md hover:bg-sky-200 font-medium text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Xem
                    </a>
                    <a href="{{ route('admin.speaking-sets.edit', $set) }}" class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Sửa
                    </a>
                    <form action="{{ route('admin.speaking-sets.destroy', $set) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc xoá bộ đề này? Các ảnh tải lên cũng sẽ bị xóa.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-1 bg-pink-100 text-pink-700 rounded-md hover:bg-pink-200 font-medium text-xs">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Xoá
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    Không có bộ đề Speaking nào trong hệ thống. <a href="{{ route('admin.speaking-sets.create') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Tạo ngay</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
@endsection
