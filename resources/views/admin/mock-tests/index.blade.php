@extends('layouts.admin')

@section('title', 'Quản lý Mock Tests')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">🧪 Mock Tests</h1>
            <p class="text-sm text-gray-500 mt-1">Theo dõi bài thi thử của học sinh</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- Export button --}}
            <a href="{{ route('admin.mock-tests.export', request()->only('skill')) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV
            </a>
            {{-- Stats mini --}}
            <div class="flex gap-3 text-sm">
                <div class="bg-white border border-gray-200 rounded-lg px-3 py-2 text-center shadow-sm">
                    <div class="font-black text-gray-800 text-lg">{{ $stats['total'] }}</div>
                    <div class="text-gray-400 text-xs">Tổng</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg px-3 py-2 text-center shadow-sm">
                    <div class="font-black text-green-700 text-lg">{{ $stats['completed'] }}</div>
                    <div class="text-green-600 text-xs">Hoàn thành</div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-center shadow-sm">
                    <div class="font-black text-amber-700 text-lg">{{ $stats['in_progress'] }}</div>
                    <div class="text-amber-600 text-xs">Đang làm</div>
                </div>
            </div>
        </div>{{-- end flex items-center gap-3 --}}
    </div>{{-- end header flex --}}

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3">
        {{-- Skill filter --}}
        <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm">
            @foreach(['all' => 'Tất cả', 'reading' => 'Reading', 'listening' => 'Listening', 'writing' => 'Writing'] as $val => $label)
                <a href="{{ route('admin.mock-tests.index', array_merge(request()->only('status'), ['skill' => $val])) }}"
                   class="px-3 py-2 text-xs font-medium {{ $skill === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-200' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Status filter --}}
        <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm">
            @foreach(['all' => 'Tất cả trạng thái', 'completed' => '✅ Hoàn thành', 'in_progress' => '⏳ Đang làm'] as $val => $label)
                <a href="{{ route('admin.mock-tests.index', array_merge(request()->only('skill'), ['status' => $val])) }}"
                   class="px-3 py-2 text-xs font-medium {{ $status === $val ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-200' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <x-datatable :data="$mockTests">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Học sinh</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kỹ năng</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày thi</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Hành động</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($mockTests as $mt)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4 text-sm text-gray-400">{{ $mt->id }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-xs font-bold">
                                {{ strtoupper(substr($mt->user->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $mt->user->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-400">{{ $mt->user->email ?? '' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $skillColors = ['reading' => 'bg-blue-100 text-blue-700', 'listening' => 'bg-green-100 text-green-700', 'writing' => 'bg-purple-100 text-purple-700'];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $skillColors[$mt->skill] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst($mt->skill) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        @if($mt->score !== null)
                            @php $s = (float)$mt->score; $c = $s>=80?'text-green-600':($s>=50?'text-amber-600':'text-red-500'); @endphp
                            <span class="font-bold {{ $c }}">{{ number_format($s, 0) }}%</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        @if($mt->duration_seconds)
                            {{ gmdate('i:s', $mt->duration_seconds) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($mt->status === 'completed')
                            <x-badge variant="success">✅ Hoàn thành</x-badge>
                        @else
                            <x-badge variant="warning">⏳ Đang làm</x-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ $mt->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($mt->status === 'completed')
                            <a href="{{ route('mock-test.result', $mt->id) }}"
                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"
                               target="_blank">
                                Xem kết quả ↗
                            </a>
                        @else
                            <span class="text-xs text-gray-400">Chưa hoàn thành</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center">
                        <div class="text-gray-400">
                            <svg class="mx-auto w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm font-medium">Chưa có bài thi thử nào</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-datatable>
</div>
@endsection
