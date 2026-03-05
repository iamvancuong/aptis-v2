@extends('layouts.admin')

@section('title', 'Báo cáo toàn lớp')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">📊 Báo cáo toàn lớp</h1>
            <p class="text-sm text-gray-500 mt-1">Thống kê kết quả học tập của từng học sinh</p>
        </div>
        <a href="{{ route('admin.reports.export', request()->only('skill', 'date_from', 'date_to')) }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export CSV
        </a>
    </div>

    {{-- Filters --}}
    <x-card>
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Kỹ năng</label>
                <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white">
                    @foreach(['all' => 'Tất cả', 'reading' => 'Reading', 'listening' => 'Listening', 'grammar' => 'Grammar', 'writing' => 'Writing'] as $val => $label)
                        <a href="{{ route('admin.reports.index', array_merge(request()->only('date_from','date_to'), ['skill' => $val])) }}"
                           class="px-3 py-2 text-xs font-medium {{ $skill === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-200' : '' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                       class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <input type="hidden" name="skill" value="{{ $skill }}">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                Lọc
            </button>
            @if($dateFrom || $dateTo)
                <a href="{{ route('admin.reports.index', ['skill' => $skill]) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
            @endif
        </form>
    </x-card>

    {{-- Table --}}
    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Học sinh</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tổng bài</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Reading</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Listening</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Avg Grammar</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Writing Mock</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Mock Tests</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">AI dùng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hết hạn</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($rows as $row)
                        @php
                            $scoreClass = fn($v) => $v === null ? 'text-gray-300' : ($v >= 80 ? 'text-green-600 font-bold' : ($v >= 50 ? 'text-amber-600 font-bold' : 'text-red-500 font-bold'));
                            $exp = $row['expiry_status'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                                        {{ strtoupper(substr($row['user']->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $row['user']->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $row['user']->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-bold text-gray-700">{{ $row['total'] }}</td>
                            <td class="px-4 py-3 text-center text-sm {{ $scoreClass($row['avg_reading']) }}">
                                {{ $row['avg_reading'] !== null ? $row['avg_reading'].'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm {{ $scoreClass($row['avg_listening']) }}">
                                {{ $row['avg_listening'] !== null ? $row['avg_listening'].'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm {{ $scoreClass($row['avg_grammar']) }}">
                                {{ $row['avg_grammar'] !== null ? $row['avg_grammar'].'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm {{ $scoreClass($row['avg_writing_mock']) }}">
                                {{ $row['avg_writing_mock'] !== null ? $row['avg_writing_mock'].'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $row['mock_count'] }}</td>
                            <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $row['ai_used'] }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($exp === 'expired')
                                    <span class="text-xs font-medium text-red-600">⛔ Hết hạn</span>
                                @elseif($exp === 'warning')
                                    <span class="text-xs font-medium text-amber-600">⚠️ Sắp hết</span>
                                @elseif($exp === 'active')
                                    <span class="text-xs text-green-600">✅ {{ $row['expires_at']?->format('d/m/Y') }}</span>
                                @else
                                    <span class="text-xs text-gray-400">Không giới hạn</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">Chưa có dữ liệu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>
@endsection
