@extends('layouts.app')

@section('title', ($title ?? 'Lịch sử làm bài') . ' - APTIS Practice')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-900">{{ $title ?? 'Lịch sử làm bài' }}</h1>
    <p class="mt-2 text-gray-600">Xem lại các bài thi và luyện tập của bạn</p>
</div>

{{-- Mode Filter Tabs --}}
@php
    $currentMode = $mode ?? 'all';
    $baseRoute = 'history.index';
    if (isset($isWriting) && $isWriting) $baseRoute = 'writingHistory.index';
    if (isset($isSpeaking) && $isSpeaking) $baseRoute = 'speakingHistory.index';
@endphp
<div class="flex flex-wrap items-center gap-3 mb-6">
    <div class="flex rounded-lg border border-gray-200 overflow-hidden bg-white shadow-sm">
        <a href="{{ route($baseRoute, array_merge(request()->only('score_min','date_from','date_to'), ['mode' => 'all'])) }}"
           class="px-4 py-2 text-sm font-medium {{ $currentMode === 'all' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            Tất cả
        </a>
        <a href="{{ route($baseRoute, array_merge(request()->only('score_min','date_from','date_to'), ['mode' => 'practice'])) }}"
           class="px-4 py-2 text-sm font-medium border-l border-gray-200 {{ $currentMode === 'practice' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            🎯 Luyện tập
        </a>
        <a href="{{ route($baseRoute, array_merge(request()->only('score_min','date_from','date_to'), ['mode' => 'mock_test'])) }}"
           class="px-4 py-2 text-sm font-medium border-l border-gray-200 {{ $currentMode === 'mock_test' ? 'bg-amber-500 text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            📝 Thi thử
        </a>
    </div>

    {{-- Advanced filter toggle --}}
    <details class="group" {{ ($dateFrom??null)||($dateTo??null)||($scoreMin??null) ? 'open' : '' }}>
        <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 select-none flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
            Lọc nâng cao {{ ($dateFrom??null)||($dateTo??null)||($scoreMin??null) ? '(đang lọc)' : '' }}
        </summary>
        <form method="GET" action="{{ route($baseRoute) }}"
              class="mt-2 flex flex-wrap gap-3 items-end bg-gray-50 border border-gray-200 rounded-lg p-3">
            <input type="hidden" name="mode" value="{{ $currentMode }}">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                <input type="date" name="date_from" value="{{ $dateFrom ?? '' }}"
                       class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                <input type="date" name="date_to" value="{{ $dateTo ?? '' }}"
                       class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Điểm tối thiểu (%)</label>
                <input type="number" name="score_min" min="0" max="100" value="{{ $scoreMin ?? '' }}"
                       placeholder="0" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm w-24">
            </div>
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">Lọc</button>
            @if(($dateFrom??null)||($dateTo??null)||($scoreMin??null))
                <a href="{{ route($baseRoute, ['mode' => $currentMode]) }}" class="text-sm text-gray-500 hover:text-gray-700">Xóa lọc</a>
            @endif
        </form>
    </details>
</div>

@if($attempts->isEmpty())
    <x-alert type="info">
        Bạn chưa có bài làm nào{{ $currentMode !== 'all' ? ' với bộ lọc này' : '' }}. Hãy bắt đầu luyện tập!
    </x-alert>
    <x-button href="{{ route('dashboard') }}" class="mt-4">
        Về Dashboard
    </x-button>
@else
    <x-card>
        <x-table>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kỹ năng</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Set đề</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điểm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Thời gian</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày làm</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($attempts as $attempt)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="capitalize font-medium">{{ $attempt->skill }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <x-badge :variant="$attempt->mode === 'mock_test' ? 'warning' : 'default'">
                                {{ $attempt->mode === 'mock_test' ? '📝 Thi thử' : '🎯 Luyện tập' }}
                            </x-badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $attempt->set->title ?? ($attempt->set->quiz->title ?? '—') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attempt->score !== null)
                                @php
                                    $s = (float) $attempt->score;
                                    $color = $s >= 80 ? 'text-green-600' : ($s >= 50 ? 'text-amber-600' : 'text-red-600');
                                @endphp
                                <span class="font-bold text-lg {{ $color }}">{{ number_format($s, 0) }}%</span>
                            @else
                                <span class="text-gray-400">Chưa chấm</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($attempt->duration_seconds)
                                {{ gmdate('H:i:s', $attempt->duration_seconds) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $attempt->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @if($attempt->mock_test_id)
                                <div class="flex items-center justify-end gap-3 text-sm">
                                    <a href="{{ route('mock-test.result', $attempt->mock_test_id) }}" class="text-blue-600 hover:text-blue-700 font-medium">
                                        KQ Mock Test
                                    </a>
                                    @if(isset($isWriting) && $isWriting)
                                        <span class="text-gray-300">|</span>
                                        <a href="{{ route('writingHistory.show', $attempt->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                            Chi tiết
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    @endif
                                    @if(isset($isSpeaking) && $isSpeaking)
                                        <span class="text-gray-300">|</span>
                                        <a href="{{ route('speakingHistory.show', $attempt->id) }}" class="text-rose-600 hover:text-rose-800 font-medium flex items-center gap-1">
                                            Chi tiết
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                        </a>
                                    @endif
                                </div>
                            @elseif(isset($isWriting) && $isWriting)
                                <a href="{{ route('writingHistory.show', $attempt->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                    Xem chi tiết →
                                </a>
                            @elseif(isset($isSpeaking) && $isSpeaking)
                                <a href="{{ route('speakingHistory.show', $attempt->id) }}" class="text-rose-600 hover:text-rose-800 font-medium text-sm">
                                    Xem chi tiết →
                                </a>
                            @elseif(in_array($attempt->skill, ['reading', 'listening', 'grammar']))
                                <a href="{{ route('history.show', $attempt->id) }}" class="text-blue-600 hover:text-blue-700 font-medium text-sm">
                                    Xem chi tiết →
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-table>
    </x-card>

    <div class="mt-4">
        {{ $attempts->links() }}
    </div>
@endif
@endsection
