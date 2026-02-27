@extends('layouts.admin')
@section('title', 'Quản lý Grammar Sets')
@section('header', 'Quản lý Grammar Sets')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Grammar & Vocabulary Sets</h1>
        <p class="text-gray-600 mt-1">Danh sách bộ đề Grammar and Vocabulary (25 MCQ + 5 Vocab Dropdown).</p>
    </div>
    <x-button href="{{ route('admin.grammar-sets.create') }}">
        + Tạo bộ đề mới
    </x-button>
</div>

@if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm font-medium">
        ✓ {{ session('success') }}
    </div>
@endif

<x-datatable :data="$sets" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên bộ đề</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số câu hỏi</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày tạo</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Thao tác</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($sets as $set)
            @php $count = $set->questions()->count(); @endphp
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ ($sets->currentPage() - 1) * $sets->perPage() + $loop->iteration }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $set->title }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($count >= 30)
                        <x-badge variant="success">Đủ 30 câu</x-badge>
                    @else
                        <x-badge variant="danger">Thiếu ({{ $count }}/30)</x-badge>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($set->status === 'published')
                        <x-badge variant="success">Published</x-badge>
                    @else
                        <x-badge variant="default">Draft</x-badge>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $set->created_at->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-2">
                    <a href="{{ route('admin.grammar-sets.edit', $set) }}"
                       class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-md hover:bg-violet-200 font-medium text-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Sửa
                    </a>
                    <form action="{{ route('admin.grammar-sets.destroy', $set) }}" method="POST" class="inline-block"
                          onsubmit="return confirm('Xóa bộ đề này? Toàn bộ câu hỏi sẽ bị xóa vĩnh viễn.')">
                        @csrf @method('DELETE')
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
                    Chưa có bộ đề Grammar nào.
                    <a href="{{ route('admin.grammar-sets.create') }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Tạo ngay</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
@endsection
