@extends('layouts.admin')

@section('title', 'Cảnh báo bảo mật')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">🛡️ Cảnh báo bảo mật — DevTools</h1>
        <p class="text-sm text-gray-500 mt-1">
            Danh sách tài khoản bị phát hiện mở Developer Tools. Tín hiệu này chỉ mang
            tính phỏng đoán và có thể báo nhầm — hãy tự xem xét trước khi khóa.
        </p>
    </div>

    {{-- Per-user summary --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">
            Tổng hợp theo tài khoản
        </div>

        @if($summary->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">Chưa có cảnh báo nào.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Học viên</th>
                            <th class="px-5 py-3 font-medium">Số lần</th>
                            <th class="px-5 py-3 font-medium">Lần gần nhất</th>
                            <th class="px-5 py-3 font-medium">Trạng thái</th>
                            <th class="px-5 py-3 font-medium text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary as $row)
                            <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                                <td class="px-5 py-3">
                                    <div class="font-semibold text-gray-800">{{ $row->user->name ?? '—' }}</div>
                                    <div class="text-gray-400 text-xs">{{ $row->user->email ?? ('#' . $row->user_id) }}</div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center justify-center min-w-7 px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-bold">
                                        {{ $row->flag_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600">
                                    {{ \Illuminate\Support\Carbon::parse($row->last_flagged_at)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-3">
                                    @if(optional($row->user)->isBlocked())
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-800 text-white text-xs">Đã khóa</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs">Đang hoạt động</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @if($row->user && ! $row->user->isBlocked() && ! $row->user->isAdmin())
                                        <form action="{{ route('admin.users.block', $row->user) }}" method="POST" class="inline-block"
                                              onsubmit="return confirm('Khóa tài khoản {{ $row->user->email }}?')">
                                            @csrf
                                            <button type="submit"
                                                    class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                                Khóa tài khoản
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.users.edit', $row->user_id) }}"
                                       class="ml-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Raw event log --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">
            Nhật ký chi tiết
        </div>

        @if($flags->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">Chưa có sự kiện nào.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Thời gian</th>
                            <th class="px-5 py-3 font-medium">Học viên</th>
                            <th class="px-5 py-3 font-medium">IP</th>
                            <th class="px-5 py-3 font-medium">Trang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($flags as $flag)
                            <tr class="border-b border-gray-50">
                                <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $flag->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="px-5 py-3">
                                    <div class="text-gray-800">{{ $flag->user->email ?? ('#' . $flag->user_id) }}</div>
                                </td>
                                <td class="px-5 py-3 text-gray-500 font-mono text-xs">{{ $flag->ip_address ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-500 text-xs max-w-xs truncate">{{ $flag->url ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $flags->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
