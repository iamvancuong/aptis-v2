@extends('layouts.app')

@section('title', 'Bảng xếp hạng - Milaedu')

@php
    $skills = ['reading', 'listening', 'writing'];
    $meta = \App\Support\SkillMeta::get($skill);
    // Hạng 1–3 tô vàng / bạc / đồng, còn lại xám.
    $rankTone = fn (int $rank) => match ($rank) {
        0 => 'bg-amber-400 text-white',
        1 => 'bg-gray-300 text-gray-700',
        2 => 'bg-orange-400 text-white',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

@section('content')
<x-ui.page-header title="Bảng xếp hạng" subtitle="Top 20 điểm thi thử cao nhất theo kỹ năng · mỗi học viên một lần tốt nhất"
    :crumbs="[['label' => 'Luyện tập', 'url' => route('dashboard')], ['label' => 'Bảng xếp hạng']]" />

<x-ui.tabs variant="pill" class="mb-5" :items="collect($skills)->map(fn ($s) => [
    'label' => \App\Support\SkillMeta::get($s)['name'],
    'url' => route('leaderboard.index', ['skill' => $s]),
    'active' => $skill === $s,
])->all()" />

@if($myBest)
    <x-ui.panel class="mb-5">
        <div class="flex items-center gap-4">
            <x-ui.avatar :name="auth()->user()->name" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-gray-900">Điểm tốt nhất của bạn · {{ $meta['name'] }}</p>
                <p class="text-xs text-gray-500">{{ $myBest->finished_at?->format('d/m/Y H:i') }}</p>
            </div>
            <x-ui.score :value="$myBest->score" class="text-base" />
        </div>
    </x-ui.panel>
@endif

@if($leaderboard->isEmpty())
    <x-ui.empty-state title="Chưa có bài thi thử nào được hoàn thành" icon="trophy">
        Hãy là người đầu tiên lên bảng — <a href="{{ route('mock-test.create', $skill) }}" class="text-blue-600 hover:text-blue-700">thi thử {{ $meta['name'] }}</a>.
    </x-ui.empty-state>
@else
    <x-ui.panel :padded="false">
        <ol class="divide-y divide-gray-100">
            @foreach($leaderboard as $rank => $mt)
                @php $isMe = $mt->user_id === auth()->id(); @endphp
                <li class="flex items-center gap-3 sm:gap-4 px-4 sm:px-5 py-3.5 {{ $isMe ? 'bg-blue-50/60' : '' }}">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 {{ $rankTone($rank) }}">{{ $rank + 1 }}</span>
                    <x-ui.avatar :name="$mt->user->name ?? '?'" size="sm" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                            {{ $mt->user->name ?? 'N/A' }}
                            @if($isMe)<span class="ml-1 text-xs font-medium text-blue-600">(Bạn)</span>@endif
                        </p>
                        <p class="flex items-center gap-2 mt-0.5 text-xs text-gray-500">
                            <span>{{ $mt->finished_at?->format('d/m/Y') ?? '—' }}</span>
                            @if($mt->duration_seconds)
                                <span class="text-gray-300">·</span>
                                <span class="inline-flex items-center gap-1"><x-ui.icon name="clock" class="w-3.5 h-3.5" />{{ gmdate($mt->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $mt->duration_seconds) }}</span>
                            @endif
                        </p>
                    </div>
                    <x-ui.score :value="$mt->score" />
                </li>
            @endforeach
        </ol>
    </x-ui.panel>
@endif
@endsection
