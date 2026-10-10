@extends('layouts.app')

@section('title', $title . ' - Milaedu')

@php
    // Dùng chung cho 3 trang lịch sử; $kind = quiz | writing | speaking (HistoryController::listing).
    $routes = ['quiz' => 'history.index', 'writing' => 'writingHistory.index', 'speaking' => 'speakingHistory.index'];
    $detailRoutes = ['quiz' => 'history.show', 'writing' => 'writingHistory.show', 'speaking' => 'speakingHistory.show'];
    $baseRoute = $routes[$kind];
    $filters = request()->only('score_min', 'date_from', 'date_to');
    $dangLoc = $dateFrom || $dateTo || ($scoreMin !== null && $scoreMin !== '');
    $modeLabel = ['all' => 'Tất cả', 'practice' => 'Luyện tập', 'mock_test' => 'Thi thử'];
@endphp

@section('content')
<x-ui.page-header :title="$title" subtitle="Xem lại điểm và chi tiết các bài luyện tập, thi thử của bạn"
    :crumbs="[['label' => 'Luyện tập', 'url' => route('dashboard')], ['label' => 'Lịch sử']]" />

<x-ui.tabs class="mb-5" :items="[
    ['label' => 'Trắc nghiệm & Ngữ pháp', 'url' => route('history.index'), 'active' => $kind === 'quiz'],
    ['label' => 'Writing', 'url' => route('writingHistory.index'), 'active' => $kind === 'writing'],
    ['label' => 'Speaking', 'url' => route('speakingHistory.index'), 'active' => $kind === 'speaking'],
]" />

{{-- Bộ lọc --}}
<div x-data="{ moLoc: {{ $dangLoc ? 'true' : 'false' }} }" class="mb-5">
    <div class="flex flex-wrap items-center gap-3">
        <x-ui.tabs variant="pill" :items="collect($modeLabel)->map(fn ($label, $key) => [
            'label' => $label,
            'url' => route($baseRoute, array_merge($filters, ['mode' => $key])),
            'active' => $mode === $key,
        ])->values()->all()" />
        <button type="button" @click="moLoc = !moLoc"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium border transition-colors
                       {{ $dangLoc ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50' }}">
            <x-ui.icon name="filter" class="w-4 h-4" />
            Lọc{{ $dangLoc ? ' (đang lọc)' : '' }}
        </button>
        <span class="ml-auto text-sm text-gray-400">{{ $attempts->total() }} bài</span>
    </div>

    <form x-cloak x-show="moLoc" x-transition method="GET" action="{{ route($baseRoute) }}"
          class="mt-3 flex flex-wrap items-end gap-3 p-4 bg-white border border-gray-200 rounded-2xl">
        <input type="hidden" name="mode" value="{{ $mode }}">
        <label class="text-xs text-gray-500">Từ ngày
            <input type="date" name="date_from" value="{{ $dateFrom }}" class="mt-1 block border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900">
        </label>
        <label class="text-xs text-gray-500">Đến ngày
            <input type="date" name="date_to" value="{{ $dateTo }}" class="mt-1 block border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900">
        </label>
        <label class="text-xs text-gray-500">Điểm tối thiểu (%)
            <input type="number" name="score_min" min="0" max="100" value="{{ $scoreMin }}" placeholder="0"
                   class="mt-1 block w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900">
        </label>
        <x-button type="submit">Áp dụng</x-button>
        @if($dangLoc)
            <a href="{{ route($baseRoute, ['mode' => $mode]) }}" class="px-2 py-2 text-sm text-gray-500 hover:text-gray-800">Xoá lọc</a>
        @endif
    </form>
</div>

@if($attempts->isEmpty())
    <x-ui.empty-state :title="$mode !== 'all' || $dangLoc ? 'Không có bài nào khớp bộ lọc' : 'Bạn chưa có bài làm nào'" icon="list">
        @if($mode !== 'all' || $dangLoc)
            Thử bỏ bớt điều kiện lọc, hoặc <a href="{{ route($baseRoute) }}" class="text-blue-600 hover:text-blue-700">xem tất cả</a>.
        @else
            Làm bài đầu tiên để theo dõi tiến độ — <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700">bắt đầu luyện tập</a>.
        @endif
    </x-ui.empty-state>
@else
    <x-ui.panel :padded="false">
        <ul class="divide-y divide-gray-100">
            @foreach($attempts as $attempt)
                @php
                    $meta = \App\Support\SkillMeta::get($attempt->skill);
                    $laThiThu = in_array($attempt->mode, ['mock', 'mock_test'], true);
                    $tenBai = $attempt->set->title ?? ($attempt->set->quiz->title ?? ($laThiThu ? 'Thi thử ' . $meta['name'] : $meta['name']));
                    $diem = $attempt->score !== null ? (float) $attempt->score : null;
                @endphp
                <li class="group relative flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-4 hover:bg-gray-50 transition-colors">
                    <x-ui.icon-badge :skill="$attempt->skill" size="sm" />
                    <div class="flex-1 min-w-0">
                        {{-- Link phủ cả dòng (after:inset-0) — link phụ bên dưới nằm trên nhờ relative z-10 --}}
                        <a href="{{ route($detailRoutes[$kind], $attempt->id) }}"
                           class="block text-sm font-semibold text-gray-900 truncate after:absolute after:inset-0">{{ $tenBai }}</a>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs text-gray-500">
                            <span class="font-medium {{ $laThiThu ? 'text-amber-600' : 'text-blue-600' }}">{{ $laThiThu ? 'Thi thử' : 'Luyện tập' }}</span>
                            @if($tenBai !== $meta['name'])
                                <span class="text-gray-300">·</span>
                                <span>{{ $meta['name'] }}</span>
                            @endif
                            <span class="text-gray-300">·</span>
                            <span>{{ ($attempt->finished_at ?? $attempt->created_at)->format('d/m/Y H:i') }}</span>
                            @if($attempt->duration_seconds)
                                <span class="text-gray-300">·</span>
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="w-3.5 h-3.5" />{{ gmdate($attempt->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $attempt->duration_seconds) }}</span>
                            @endif
                            @if($laThiThu && $attempt->mock_test_id && $kind !== 'quiz')
                                <span class="text-gray-300">·</span>
                                <a href="{{ route('mock-test.result', $attempt->mock_test_id) }}" class="relative z-10 text-blue-600 hover:text-blue-700">Kết quả thi thử</a>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($diem !== null && $attempt->skill === 'writing' && $diem > 0)
                            @php $aptis = \App\Support\AptisScale::writing($diem); @endphp
                            <span class="hidden sm:inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $aptis['color']['badge'] }}"
                                  title="Điểm Aptis ước tính {{ $aptis['scale'] }}/50">{{ $aptis['level'] }} · {{ $aptis['scale'] }}/50</span>
                        @endif
                        <x-ui.score :value="$diem" />
                        <span class="text-gray-300 group-hover:text-blue-500 transition-colors" aria-hidden="true">→</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </x-ui.panel>

    @if($attempts->hasPages())
        <div class="mt-4">{{ $attempts->links() }}</div>
    @endif
@endif
@endsection
