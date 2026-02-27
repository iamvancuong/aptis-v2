@extends('layouts.app')

@section('title', 'Chi tiết bài làm - ' . ucfirst($attempt->skill))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('history.index') }}"
               class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Quay lại lịch sử
            </a>
            <h1 class="text-2xl font-bold text-gray-900">
                Kết quả: {{ ucfirst($attempt->skill) }} — {{ $attempt->set->title ?? '—' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Loại: <x-badge :variant="$attempt->mode === 'mock_test' ? 'warning' : 'default'" class="inline">
                    {{ $attempt->mode === 'mock_test' ? '📝 Thi thử' : '🎯 Luyện tập' }}
                </x-badge>
                · Hoàn thành lúc {{ $attempt->created_at->format('H:i d/m/Y') }}
            </p>
        </div>
        <div class="text-right">
            @php $s = (float) ($attempt->score ?? 0); $color = $s >= 80 ? 'text-green-600' : ($s >= 50 ? 'text-amber-600' : 'text-red-500'); @endphp
            <span class="text-4xl font-black {{ $color }}">{{ number_format($s, 0) }}%</span>
            <p class="text-sm text-gray-500">Tổng điểm</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    @php
        $answers = $attempt->attemptAnswers;
        $total   = $answers->count();
        $correct = $answers->where('is_correct', true)->count();
        $wrong   = $answers->where('is_correct', false)->count();
        $skip    = $total - $correct - $wrong;
    @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-green-50 rounded-xl p-4 text-center border border-green-100">
            <div class="text-2xl font-black text-green-600">{{ $correct }}</div>
            <div class="text-xs font-semibold text-green-700 mt-1">✅ Đúng</div>
        </div>
        <div class="bg-red-50 rounded-xl p-4 text-center border border-red-100">
            <div class="text-2xl font-black text-red-500">{{ $wrong }}</div>
            <div class="text-xs font-semibold text-red-600 mt-1">❌ Sai</div>
        </div>
        <div class="bg-gray-50 rounded-xl p-4 text-center border border-gray-200">
            <div class="text-2xl font-black text-gray-500">{{ $total }}</div>
            <div class="text-xs font-semibold text-gray-500 mt-1">📋 Tổng câu</div>
        </div>
    </div>

    {{-- Per-Part breakdown --}}
    @php
        $byPart = $answers->groupBy(fn($a) => $a->question->part ?? '?');
    @endphp
    @if($byPart->count() > 1)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <h3 class="font-bold text-gray-700 text-sm">Kết quả từng Part</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($byPart->sortKeys() as $part => $partAnswers)
                    @php
                        $pTotal   = $partAnswers->count();
                        $pCorrect = $partAnswers->where('is_correct', true)->count();
                        $pPct     = $pTotal > 0 ? round($pCorrect / $pTotal * 100) : 0;
                        $pColor   = $pPct >= 80 ? 'bg-green-500' : ($pPct >= 50 ? 'bg-amber-400' : 'bg-red-400');
                    @endphp
                    <div class="px-5 py-3 flex items-center gap-4">
                        <span class="text-sm font-bold text-gray-700 w-16 shrink-0">Part {{ $part }}</span>
                        <div class="flex-1 bg-gray-100 rounded-full h-2.5">
                            <div class="{{ $pColor }} h-2.5 rounded-full transition-all" style="width: {{ $pPct }}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-gray-600 w-24 text-right shrink-0">
                            {{ $pCorrect }}/{{ $pTotal }} ({{ $pPct }}%)
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Questions Detail --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
            <h3 class="font-bold text-gray-700 text-sm">Chi tiết từng câu</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($answers->sortBy(fn($a) => [$a->question->part ?? 0, $a->question->order ?? 0]) as $i => $answer)
                @php
                    $q = $answer->question;
                    $isCorrect = $answer->is_correct;
                    $userAns = $answer->answer;
                    $correctAns = $q->metadata['correct_answer'] ?? ($q->metadata['correct_answers'] ?? null);
                    $rowBg = $isCorrect ? 'bg-green-50/40' : 'bg-red-50/40';
                    $icon = $isCorrect ? '✅' : '❌';
                @endphp
                <div class="px-5 py-4 {{ $rowBg }}">
                    <div class="flex items-start gap-3">
                        <span class="text-base shrink-0 mt-0.5">{{ $icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-bold text-gray-400 uppercase">Part {{ $q->part ?? '?' }} · Câu {{ $i + 1 }}</span>
                                @if($q->title)
                                    <span class="text-xs text-gray-500">— {{ $q->title }}</span>
                                @endif
                            </div>
                            @if($q->stem)
                                <p class="text-sm text-gray-700 mb-2 font-medium line-clamp-2">{{ $q->stem }}</p>
                            @endif
                            <div class="flex flex-wrap gap-4 text-xs">
                                <div>
                                    <span class="text-gray-500">Bạn chọn:</span>
                                    <span class="font-semibold {{ $isCorrect ? 'text-green-700' : 'text-red-600' }} ml-1">
                                        @if(is_array($userAns))
                                            {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $userAns)) }}
                                        @else
                                            {{ $userAns ?: '(Bỏ trống)' }}
                                        @endif
                                    </span>
                                </div>
                                @if(!$isCorrect && $correctAns !== null)
                                    <div>
                                        <span class="text-gray-500">Đáp án đúng:</span>
                                        <span class="font-semibold text-green-700 ml-1">
                                            @if(is_array($correctAns))
                                                {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $correctAns)) }}
                                            @else
                                                {{ $correctAns }}
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="text-sm font-bold {{ $isCorrect ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $isCorrect ? '+'.number_format($answer->score, 1) : '0' }}đ
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <a href="{{ route('history.index') }}"
           class="flex-1 text-center px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-colors text-sm">
            ← Về lịch sử
        </a>
        <a href="{{ $attempt->skill === 'grammar' ? route('grammar.index') : route('skills.show', $attempt->skill) }}"
           class="flex-1 text-center px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-xl hover:bg-indigo-700 transition-colors text-sm">
            Luyện tập lại
        </a>
    </div>
</div>
@endsection
