@extends('layouts.admin')

@section('title', 'Cảnh báo bảo mật')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">🛡️ Cảnh báo bảo mật</h1>
        <p class="text-sm text-gray-500 mt-1">
            Hai loại cảnh báo tách riêng bên dưới: <strong>đăng nhập nhiều thiết bị</strong> và
            <strong>mở Developer Tools</strong>. Cả hai đều là tín hiệu để xem xét, không phải bằng chứng —
            hãy tự đánh giá trước khi khoá tài khoản.
        </p>
    </div>

    {{-- ① Tài khoản đang mang vi phạm thiết bị.
         Nguồn là `users.violation_count` chứ không phải `security_flags`: cột đó
         đã tích luỹ từ trước khi có log, nên đây là chỗ DUY NHẤT thấy được tình
         trạng của những tài khoản vi phạm trước đó. Đổi lại nó chỉ là con số. --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">
            📱 Tài khoản đang mang vi phạm thiết bị ({{ $dangViPham->count() }})
        </div>

        @if($dangViPham->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">Không có tài khoản nào đang mang vi phạm.</div>
        @else
            <div class="px-5 py-3 text-xs text-gray-500 border-b border-gray-100">
                Vi phạm cũ hơn {{ config('devices.violation_reset_days') }} ngày không còn cộng dồn, và bấm
                <strong>Unblock</strong> ở trang Người dùng sẽ reset số này về 0. Khoá tự động khi đủ
                <strong>{{ config('devices.block_after_violations') }}</strong> lần.
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Học viên</th>
                            <th class="px-5 py-3 font-medium">Vi phạm</th>
                            <th class="px-5 py-3 font-medium">Gần nhất</th>
                            <th class="px-5 py-3 font-medium">Phiên đang mở</th>
                            <th class="px-5 py-3 font-medium">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($dangViPham as $u)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-blue-600 hover:text-blue-700">{{ $u->name }}</a>
                                    <div class="text-xs text-gray-500">{{ $u->email }}</div>
                                </td>
                                <td class="px-5 py-3 font-semibold text-gray-800">{{ $u->violation_count }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $u->last_violation_at?->format('H:i d/m/Y') ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $u->login_sessions_count }} / {{ $u->max_devices }}</td>
                                <td class="px-5 py-3">
                                    @if($u->status === 'blocked')
                                        <span class="px-2 py-1 text-xs rounded-lg bg-red-100 text-red-700">Đang khoá</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-lg bg-gray-100 text-gray-700">Đang hoạt động</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ② Nhật ký từng lần vi phạm thiết bị — có IP và trình duyệt, thứ mà
         `violation_count` không nói được. Chỉ có dữ liệu từ khi bật ghi log. --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">
            📱 Nhật ký vi phạm thiết bị
        </div>

        @if($flagsThietBi->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">
                Chưa ghi được lần vi phạm nào. Nhật ký chỉ có từ lúc bật tính năng ghi log —
                tài khoản vi phạm trước đó vẫn hiện ở bảng trên.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Thời điểm</th>
                            <th class="px-5 py-3 font-medium">Học viên</th>
                            <th class="px-5 py-3 font-medium">Địa chỉ mạng</th>
                            <th class="px-5 py-3 font-medium">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($flagsThietBi as $f)
                            <tr>
                                <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $f->created_at->format('H:i d/m/Y') }}</td>
                                <td class="px-5 py-3">
                                    @if($f->user)
                                        <a href="{{ route('admin.users.show', $f->user) }}" class="font-medium text-blue-600 hover:text-blue-700">{{ $f->user->name }}</a>
                                        <div class="text-xs text-gray-500">{{ $f->user->email }}</div>
                                    @else
                                        <span class="text-gray-400">Tài khoản đã xoá</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $f->ip_address }}</td>
                                <td class="px-5 py-3 text-xs text-gray-600">{{ $f->url }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3">{{ $flagsThietBi->links() }}</div>
        @endif
    </div>

    <div class="pt-2">
        <h2 class="text-lg font-bold text-gray-900">🧰 Mở Developer Tools</h2>
        <p class="text-sm text-gray-500 mt-1">
            Tín hiệu phỏng đoán và có thể báo nhầm — xem xét kỹ trước khi khoá.
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
