@extends('layouts.admin')

@section('title', 'Nhật ký vào lớp - Admin')
@section('header', 'Nhật ký vào lớp')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.class-sessions.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Quay lại danh sách buổi học
    </a>
</div>

<x-card>
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-800">{{ $classSession->title }}</h2>
        <p class="text-sm text-gray-500 mt-1">
            {{ $classSession->timeLabel() }} · <strong>{{ $joins->total() }}</strong> lượt vào lớp
        </p>
        <p class="text-xs text-gray-500 mt-2">
            Dấu hiệu chia sẻ link đáng ngờ nhất: <strong>một tài khoản vào từ nhiều địa chỉ mạng khác nhau</strong>
            trong cùng buổi. Những dòng đó được đánh dấu vàng bên dưới.
        </p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Học viên</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời điểm</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Địa chỉ mạng</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thiết bị</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($joins as $join)
                    @php $nhieuIp = ($ipCounts[$join->user_id] ?? 1) > 1; @endphp
                    <tr class="{{ $nhieuIp ? 'bg-amber-50' : 'hover:bg-gray-50' }} transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $join->user->name ?? '(đã xoá)' }}</div>
                            <div class="text-xs text-gray-500">{{ $join->user->email ?? '' }}</div>
                            @if($nhieuIp)
                                <x-badge variant="warning" class="mt-1">
                                    Vào từ {{ $ipCounts[$join->user_id] }} mạng khác nhau
                                </x-badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 tabular-nums">
                            {{ $join->created_at->format('H:i:s d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-600">
                            {{ $join->ip_address ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-500 max-w-md truncate" title="{{ $join->user_agent }}">
                                {{ $join->user_agent ?? '—' }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <p class="text-sm font-medium">Chưa có ai vào buổi học này.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($joins->hasPages())
        <div class="mt-6">{{ $joins->links() }}</div>
    @endif
</x-card>
@endsection
