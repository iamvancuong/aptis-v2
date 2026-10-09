@extends('layouts.app')

@php
    $meta = \App\Support\SkillMeta::get($skill);
    $partLabel = \App\Support\PartLabel::text($skill, $part);
    $daLam = $setStats->count();
@endphp

@section('title', $meta['name'] . ' ' . $partLabel . ' - Milaedu')

@section('content')
<x-ui.page-header
    :skill="$skill"
    :title="$meta['name'] . ' · ' . $partLabel"
    :subtitle="$quiz->title"
    :crumbs="[
        ['label' => 'Luyện tập', 'url' => route('dashboard')],
        ['label' => $meta['name'], 'url' => route('skills.show', $skill)],
        ['label' => $partLabel],
    ]">
    <x-slot:aside>
        <x-ui.chip><span class="font-semibold text-gray-900">{{ $sets->count() }}</span> đề</x-ui.chip>
        @if($quiz->duration_minutes)
            <x-ui.chip><x-ui.icon name="clock" class="w-4 h-4 text-gray-400" /> {{ $quiz->duration_minutes }} phút</x-ui.chip>
        @endif
        <x-ui.chip :tone="$daLam > 0 ? 'blue' : 'gray'">Đã làm <span class="font-semibold">{{ $daLam }}/{{ $sets->count() }}</span></x-ui.chip>
    </x-slot:aside>
</x-ui.page-header>

@if($sets->isEmpty())
    <x-ui.empty-state title="Chưa có đề nào được công bố cho Part này" icon="list">
        Bạn có thể quay lại luyện các Part khác hoặc thi thử toàn bộ kỹ năng.
    </x-ui.empty-state>
@else
    <x-ui.section-title title="Chọn đề để luyện" meta="Đề đã làm hiện điểm cao nhất" />
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
        @foreach($sets as $set)
            @php $kq = $setStats[$set->id] ?? null; @endphp
            <x-ui.tile>
                <div class="flex items-start justify-between gap-3">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $meta['tone'] }}">Đề {{ $loop->iteration }}</span>
                    @if($kq)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                            <x-ui.icon name="check" class="w-4 h-4" /> Đã làm {{ $kq->times }} lần
                        </span>
                    @endif
                </div>
                <h3 class="mt-3 text-base font-semibold text-gray-900">{{ $set->title }}</h3>
                <p class="mt-1 text-xs text-gray-500">{{ $set->questions_count }} câu hỏi</p>

                <x-ui.progress class="mt-4"
                    :value="$kq?->best ?? 0"
                    :label="$kq ? 'Điểm cao nhất' : 'Chưa làm'"
                    :hint="$kq && $kq->best !== null ? round((float) $kq->best) . '%' : null" />

                <div class="mt-4 pt-4 border-t border-gray-100">
                    <a href="{{ route('practice.show', $set->id) }}"
                       class="w-full inline-flex items-center justify-center px-3 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        {{ $kq ? 'Luyện lại' : 'Bắt đầu luyện' }} →
                    </a>
                </div>
            </x-ui.tile>
        @endforeach
    </div>
@endif
@endsection
