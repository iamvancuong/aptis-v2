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
            {{-- Nhãn phạm vi luôn hiện. Không có nó thì "12.400.000đ" là con số
                 không đọc được — của tháng này hay của cả năm? --}}
            <p class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Đang xem: {{ $period['label'] }}
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:items-end">
            {{-- Hai phạm vi hay dùng nhất, một chạm. --}}
            <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden self-start sm:self-auto">
                <a href="{{ route('admin.revenue.index') }}"
                   class="px-4 py-2 text-sm font-medium {{ $period['mode'] === 'thang' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    Tháng này
                </a>
                <a href="{{ route('admin.revenue.index', ['range' => 'tat_ca']) }}"
                   class="px-4 py-2 text-sm font-medium border-l border-gray-300 {{ $period['mode'] === 'tat_ca' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    Tổng
                </a>
            </div>

            {{-- Bộ lọc thời gian tuỳ chọn (xem lại tháng cũ, hoặc một khoảng bất kỳ) --}}
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
                @if($period['mode'] === 'tuy_chon')
                    <a href="{{ route('admin.revenue.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
                @endif
            </form>
        </div>
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

    {{-- Doanh số theo Sale --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">Doanh số theo Sale <span class="text-xs font-normal text-gray-400">(chỉ đơn đăng ký đã thanh toán)</span></div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-100">
                        <th class="px-5 py-3 font-medium">Sale</th>
                        <th class="px-5 py-3 font-medium text-right">Số đơn</th>
                        <th class="px-5 py-3 font-medium text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales['rows'] as $row)
                        <tr class="border-b border-gray-50">
                            <td class="px-5 py-3 text-gray-800">
                                <span class="font-semibold">{{ $row['name'] }}</span>
                                <span class="ml-1 text-xs text-gray-400 font-mono">{{ $row['code'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-gray-900">{{ $row['count'] }}</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-600">{{ $fmt($row['revenue']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="border-b border-gray-50 bg-gray-50/50">
                        <td class="px-5 py-3 text-gray-500">Không qua sale</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $sales['no_sale']['count'] }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ $fmt($sales['no_sale']['revenue']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Link giới thiệu để admin copy gửi cho sale --}}
        @if(!empty($sales['links']))
            <div class="px-5 py-4 border-t border-gray-100 bg-slate-50" x-data="{ copied: '' }">
                <div class="text-sm font-semibold text-gray-700 mb-3">🔗 Link gửi cho sale <span class="font-normal text-gray-400 text-xs">(bấm để copy)</span></div>
                <div class="space-y-3">
                    @foreach($sales['links'] as $lk)
                        <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                            <div class="w-28 shrink-0 text-sm font-medium text-gray-800">{{ $lk['name'] }} <span class="text-gray-400 font-mono text-xs">{{ $lk['code'] }}</span></div>
                            @foreach(['thang' => 'Gói tháng', 'tuan' => 'Gói tuần'] as $key => $lbl)
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $lk[$key] }}'); copied='{{ $lk['code'].$key }}'; setTimeout(() => copied='', 1500)"
                                        class="group flex-1 min-w-0 flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-left hover:border-blue-400 transition">
                                    <span class="shrink-0 text-xs font-semibold px-1.5 py-0.5 rounded bg-blue-50 text-blue-700">{{ $lbl }}</span>
                                    <span class="truncate text-xs text-gray-500 font-mono">{{ $lk[$key] }}</span>
                                    <span class="shrink-0 ml-auto text-xs font-medium"
                                          :class="copied === '{{ $lk['code'].$key }}' ? 'text-emerald-600' : 'text-gray-400 group-hover:text-blue-600'"
                                          x-text="copied === '{{ $lk['code'].$key }}' ? '✓ Đã copy' : 'Copy'"></span>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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
                                    @if($o->sale_code)
                                        <span class="ml-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-mono">{{ $o->sale_code }}</span>
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
