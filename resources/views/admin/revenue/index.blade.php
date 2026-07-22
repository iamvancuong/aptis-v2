@extends('layouts.admin')

@section('title', 'Doanh số')

@php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.') . 'đ';
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">💰 Doanh số</h1>
            <p class="text-sm text-gray-500 mt-1">Tính từ các đơn đã thanh toán. Không bao gồm thuế.</p>
        </div>

        {{-- Bộ lọc thời gian --}}
        <form method="GET" class="flex items-end gap-2">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Lọc</button>
            @if(($filters['from'] ?? null) || ($filters['to'] ?? null))
                <a href="{{ route('admin.revenue.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
            @endif
        </form>
    </div>

    {{-- Tổng --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 text-white rounded-2xl p-5 shadow-lg">
            <div class="text-xs uppercase tracking-wider text-blue-100">Tổng doanh thu</div>
            <div class="text-3xl font-black mt-1">{{ $fmt($summary['total']) }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-gray-400">Doanh thu đăng ký</div>
            <div class="text-2xl font-extrabold text-gray-900 mt-1">{{ $fmt($summary['registration']) }}</div>
            <div class="text-xs text-gray-400 mt-1">Chia {{ $summary['split']['co_dung'] }}/{{ $summary['split']['cuong'] }}/{{ $summary['split']['con_lai'] }}</div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm">
            <div class="text-xs uppercase tracking-wider text-gray-400">Doanh thu chấm bài</div>
            <div class="text-2xl font-extrabold text-gray-900 mt-1">{{ $fmt($summary['grading']) }}</div>
            <div class="text-xs text-gray-400 mt-1">Riêng — 100% Cô Dung</div>
        </div>
    </div>

    {{-- Chia --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">Phân chia</div>
        <div class="divide-y divide-gray-50">
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <div class="font-bold text-gray-900">Cô Dung</div>
                    <div class="text-xs text-gray-400">{{ $summary['split']['co_dung'] }}% đăng ký ({{ $fmt($summary['co_dung_reg']) }}) + 100% chấm bài ({{ $fmt($summary['grading']) }})</div>
                </div>
                <div class="text-xl font-black text-emerald-600">{{ $fmt($summary['co_dung_total']) }}</div>
            </div>
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <div class="font-bold text-gray-900">Cường</div>
                    <div class="text-xs text-gray-400">{{ $summary['split']['cuong'] }}% doanh thu đăng ký</div>
                </div>
                <div class="text-xl font-black text-gray-800">{{ $fmt($summary['cuong']) }}</div>
            </div>
            <div class="flex items-center justify-between px-5 py-4">
                <div>
                    <div class="font-bold text-gray-900">Còn lại</div>
                    <div class="text-xs text-gray-400">{{ $summary['split']['con_lai'] }}% doanh thu đăng ký</div>
                </div>
                <div class="text-xl font-black text-gray-800">{{ $fmt($summary['con_lai']) }}</div>
            </div>
        </div>
    </div>

    {{-- Lịch sử --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">Lịch sử giao dịch</div>
        @if($orders->isEmpty())
            <div class="px-5 py-10 text-center text-gray-400">Chưa có giao dịch nào.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100">
                            <th class="px-5 py-3 font-medium">Ngày</th>
                            <th class="px-5 py-3 font-medium">Email</th>
                            <th class="px-5 py-3 font-medium">Loại</th>
                            <th class="px-5 py-3 font-medium">Mã đơn</th>
                            <th class="px-5 py-3 font-medium text-right">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $o)
                            <tr class="border-b border-gray-50">
                                <td class="px-5 py-3 whitespace-nowrap text-gray-600">{{ optional($o->paid_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 text-gray-800 break-all">{{ $o->email }}</td>
                                <td class="px-5 py-3">
                                    @if($o->type === \App\Models\Order::TYPE_GRADING)
                                        <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs">Chấm bài</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs">Đăng ký {{ $o->package }} ×{{ $o->quantity }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $o->order_code }}</td>
                                <td class="px-5 py-3 text-right font-bold text-gray-900">{{ $fmt($o->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-gray-100">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
