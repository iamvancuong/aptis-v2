@extends('layouts.admin')

@section('title', 'Lớp học - Admin')
@section('header', 'Lớp học')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <p class="text-sm text-gray-600">
        Mỗi lớp là một nhóm học viên cố định, dùng chung một phòng Meet.
        Buổi học gắn vào lớp thì <strong>chỉ thành viên lớp đó</strong> vào được.
    </p>
    <a href="{{ route('admin.class-groups.create') }}"
       class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shrink-0">
        + Tạo lớp mới
    </a>
</div>

@if(session('error'))
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-200 rounded-lg text-sm text-green-800">{{ session('success') }}</div>
@endif

<x-card>
    @if($groups->isEmpty())
        <p class="text-sm text-gray-500 italic py-4 text-center">
            Chưa có lớp nào. Buổi học chưa gắn lớp vẫn mở cho mọi học viên còn hạn.
        </p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-200">
                        <th class="py-2 pr-4">Tên lớp</th>
                        <th class="py-2 pr-4">Thành viên</th>
                        <th class="py-2 pr-4">Buổi học</th>
                        <th class="py-2 pr-4">Phòng Meet</th>
                        <th class="py-2 pr-4">Trạng thái</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($groups as $g)
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="font-medium text-gray-800">{{ $g->name }}</span>
                                @if($g->source_filter)
                                    <span class="block text-xs text-gray-500">
                                        Ưu tiên: {{ \App\Models\User::SOURCE_LABELS[$g->source_filter] ?? $g->source_filter }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-gray-700">{{ $g->members_count }}</td>
                            <td class="py-3 pr-4 text-gray-700">{{ $g->sessions_count }}</td>
                            <td class="py-3 pr-4">
                                @if($g->meet_link)
                                    <x-badge variant="success">Đã có link</x-badge>
                                @else
                                    <x-badge variant="warning">Chưa dán link</x-badge>
                                @endif
                            </td>
                            <td class="py-3 pr-4">
                                @if($g->is_active)
                                    <x-badge variant="success">Đang bật</x-badge>
                                @else
                                    <x-badge>Đã tắt</x-badge>
                                @endif
                            </td>
                            <td class="py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.class-groups.members', $g) }}"
                                   class="text-blue-600 hover:text-blue-700 font-medium">Thành viên</a>
                                <a href="{{ route('admin.class-groups.edit', $g) }}"
                                   class="ml-3 text-gray-600 hover:text-gray-800">Sửa</a>
                                <form action="{{ route('admin.class-groups.destroy', $g) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Xoá lớp “{{ $g->name }}”? Thành viên không bị xoá, chỉ gỡ khỏi lớp.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ml-3 text-red-600 hover:text-red-700">Xoá</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $groups->links() }}</div>
    @endif
</x-card>

{{-- Nhắc lại giới hạn thật, ngay chỗ admin dễ hiểu nhầm nhất: web chọn được
     thành viên theo từng buổi, nhưng Google chỉ biết danh sách mời của cả chuỗi. --}}
<div class="mt-6 flex gap-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <div class="text-xs text-amber-800">
        <p class="font-semibold mb-1">Danh sách trên web và danh sách mời Meet là HAI thứ khác nhau</p>
        <p>
            Web quyết định <strong>ai lấy được link</strong>. Google quyết định <strong>ai vào thẳng phòng</strong> —
            và Google chỉ biết danh sách khách mời trên sự kiện Calendar, nó không đọc được dữ liệu Milaedu.
        </p>
        <p class="mt-1">
            Nên mỗi lần đổi thành viên lớp, phải <strong>cập nhật lại ô Khách mời</strong> của sự kiện Calendar
            (copy danh sách ở màn “Thành viên” của lớp). Không cập nhật thì người mới phải xin duyệt,
            còn người đã gỡ vẫn vào thẳng được.
        </p>
    </div>
</div>

@endsection
