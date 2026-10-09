@extends('layouts.app')

@php
    $meta = \App\Support\SkillMeta::get($skill);
    $chiThiThu = \App\Support\SkillMeta::mockOnly($skill);
@endphp

@section('title', $meta['name'] . ' - Milaedu')

@section('content')
<x-ui.page-header
    :skill="$skill"
    :title="$meta['name']"
    :subtitle="$meta['desc'] . ($chiThiThu ? ' · luyện dưới dạng thi thử toàn bộ 4 Part' : ' · chọn Part để luyện hoặc thi thử cả kỹ năng')"
    :crumbs="[['label' => 'Luyện tập', 'url' => route('dashboard')], ['label' => $meta['name']]]">
    <x-slot:aside>
        <x-ui.chip><span class="font-semibold text-gray-900">{{ $skillStats['attempts'] }}</span> bài đã làm</x-ui.chip>
        <x-ui.chip>Thi thử gần nhất <span class="font-semibold text-gray-900">{{ $skillStats['last_mock'] !== null ? round((float) $skillStats['last_mock']) . '%' : '—' }}</span></x-ui.chip>
    </x-slot:aside>
</x-ui.page-header>

<x-ui.cta-card :href="route('mock-test.create', $skill)" icon="clock" :title="'Thi thử ' . $meta['name']" action="Bắt đầu thi thử">
    Làm trọn bộ các Part có tính giờ và chấm điểm như thi thật
</x-ui.cta-card>

@if(! $chiThiThu)
    <x-ui.section-title title="Luyện theo Part" :meta="$quizzes->count() . ' Part'" />

    @if($quizzes->isEmpty())
        <x-ui.empty-state title="Chưa có Part nào được công bố" icon="list">
            Bạn có thể luyện bằng thi thử toàn bộ kỹ năng ở trên.
        </x-ui.empty-state>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
            @foreach($quizzes as $quiz)
                @php
                    $tienDo = $partProgress[$quiz->id] ?? null;
                    $tong = $quiz->public_sets_count;
                    $daLam = min($tienDo->done ?? 0, $tong);
                @endphp
                {{-- Nhãn theo đề thật (Reading: 2→"2-3", 3→"4", 4→"5"); link vẫn dùng số nội bộ $quiz->part. --}}
                <x-ui.tile :href="route('sets.index', [$skill, $quiz->part])">
                    <span class="self-start inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold {{ $meta['tone'] }}">
                        {{ \App\Support\PartLabel::text($skill, $quiz->part) }}
                    </span>
                    <span class="block mt-3 text-base font-semibold text-gray-900">{{ $quiz->title }}</span>
                    <span class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-500">
                        <span>{{ $tong }} đề</span>
                        @if($quiz->duration_minutes)<span>{{ $quiz->duration_minutes }} phút</span>@endif
                        @if($tienDo && $tienDo->avg_score !== null)<span>Điểm TB {{ round((float) $tienDo->avg_score) }}%</span>@endif
                    </span>
                    <x-ui.progress class="mt-auto pt-4"
                        :value="$tong > 0 ? $daLam / $tong * 100 : 0"
                        :label="$daLam > 0 ? 'Đã làm ' . $daLam . '/' . $tong . ' đề' : 'Chưa làm'"
                        :hint="$daLam > 0 ? round($daLam / $tong * 100) . '%' : null" />
                </x-ui.tile>
            @endforeach
        </div>
    @endif
@else
    <x-ui.panel>
        <div class="flex gap-4">
            <x-ui.icon-badge icon="info" tone="bg-blue-50 text-blue-600" size="sm" />
            <div class="text-sm">
                <p class="font-medium text-gray-900">Vì sao chỉ có thi thử?</p>
                <p class="text-gray-500 mt-1">
                    {{ $meta['name'] }} đòi hỏi các Part (1 đến 4) liên kết ngữ cảnh chặt chẽ với nhau,
                    nên bạn luyện bằng <span class="font-medium text-gray-700">thi thử toàn bộ kỹ năng</span> để sát đề thật nhất.
                </p>
                <a href="{{ route($skill === 'writing' ? 'writingHistory.index' : 'speakingHistory.index') }}"
                   class="inline-block mt-3 text-blue-600 hover:text-blue-700 font-medium">Xem bài đã làm →</a>
            </div>
        </div>
    </x-ui.panel>
@endif
@endsection
