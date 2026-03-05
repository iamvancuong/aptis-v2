@extends('layouts.admin')

@section('title', 'Quản lý Bộ Đề (Sets)')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">Quản lý Bộ Đề</h1>
    <div class="flex gap-2 items-start mt-2 sm:mt-0">
        <button id="bulk-delete-btn" style="display: none;" onclick="bulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm shadow-sm transition-all items-center">
            <svg class="w-4 h-4 mr-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            Xoá đã chọn (<span class="count">0</span>)
        </button>
        <x-button href="{{ route('admin.sets.create') }}">
            + Thêm Bộ Đề
        </x-button>
    </div>
</div>

{{-- @if(session('success'))
    <x-alert type="success" class="mb-4">
        {{ session('success') }}
    </x-alert>
@endif --}}

<!-- Sets Datatable -->
<x-datatable :data="$sets" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 w-10 text-left text-xs font-medium text-gray-500 uppercase">
                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chủ đề (Title)</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quiz</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thứ tự</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($sets as $set)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" value="{{ $set->id }}" class="bulk-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ ($sets->currentPage() - 1) * $sets->perPage() + $loop->iteration }}</td>
                <td class="px-6 py-4 text-sm">{{ $set->title }}</td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex items-center gap-2">
                        <x-badge :variant="$set->quiz->skill === 'reading' ? 'default' : ($set->quiz->skill === 'listening' ? 'success' : 'warning')">
                            {{ ucfirst($set->quiz->skill) }}
                        </x-badge>
                        <span class="text-xs text-gray-600">Part {{ $set->quiz->part }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $set->quiz->title }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $set->order + 1 }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$set->is_public ? 'success' : 'danger'">
                        {{ $set->is_public ? 'Công khai' : 'Nháp' }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                    <a href="{{ route('admin.sets.edit', $set) }}" class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Sửa
                    </a>
                    <form id="delete-form-{{ $set->id }}" action="{{ route('admin.sets.destroy', $set) }}" method="POST" class="inline-block" onsubmit="return confirm('Bạn có chắc xoá bộ đề này?')">
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
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                    Không có bộ đề nào trong hệ thống. <a href="{{ route('admin.sets.create') }}" class="text-blue-600">Tạo mới</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
@endsection
