@extends('layouts.app')

@section('title', ucfirst($mockTest->skill) . ' - Kết quả thi thử')

@section('content')
<div class="mb-8">
    <a href="{{ route('skills.show', $mockTest->skill) }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Quay lại {{ ucfirst($mockTest->skill) }}
    </a>
    <h1 class="text-3xl font-bold text-gray-900">Kết quả thi thử {{ ucfirst($mockTest->skill) }}</h1>
    <p class="text-gray-500 mt-1">
        Hoàn thành lúc {{ $mockTest->finished_at->format('H:i d/m/Y') }}
        — Thời gian: {{ gmdate('i:s', $mockTest->duration_seconds) }}
    </p>
</div>

<div class="max-w-3xl mx-auto">

    {{-- Overall Score Card --}}
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
        <div class="text-center">
            @php
                $score = $mockTest->score ?? 0;
                $scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-600');
                $scoreBg = $score >= 80 ? 'from-green-50 to-emerald-50' : ($score >= 50 ? 'from-amber-50 to-yellow-50' : 'from-red-50 to-orange-50');
            @endphp
            <div class="inline-flex items-center justify-center w-32 h-32 rounded-full bg-gradient-to-br {{ $scoreBg }} mb-4">
                <span class="text-4xl font-black {{ $scoreColor }}">{{ number_format($score, 0) }}%</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-1">
                @if($score >= 80) Xuất sắc! 🎉
                @elseif($score >= 60) Tốt! 👍
                @elseif($score >= 40) Cần cải thiện 📚
                @else Cần ôn tập thêm 💪
                @endif
            </h2>
            @if($mockTest->skill !== 'writing')
                <p class="text-gray-500">
                    {{ number_format($score, 1) }}% câu trả lời đúng
                </p>
            @else
                <p class="text-amber-600">
                    ⏳ Bài viết đang chờ chấm điểm
                </p>
            @endif
        </div>
    </div>

    {{-- Per-Section Breakdown --}}
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-8">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">Chi tiết từng section</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($sectionsWithSets as $index => $section)
                @php
                    $sectionScore = $mockTest->section_scores[$index] ?? null;
                    $attempt = $attempts->firstWhere('set_id', $section['set_id']);
                @endphp
                <div class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <div class="font-semibold text-gray-800">Part {{ $section['part'] }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $section['set']->quiz->title ?? '' }}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        @if($mockTest->skill === 'writing')
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">
                                ⏳ Chờ chấm
                            </span>
                        @elseif($sectionScore !== null)
                            @php
                                $color = $sectionScore >= 80 ? 'green' : ($sectionScore >= 50 ? 'amber' : 'red');
                            @endphp
                            <span class="text-2xl font-black text-{{ $color }}-600">
                                {{ number_format($sectionScore, 0) }}%
                            </span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex flex-col sm:flex-row gap-4">
        @if($mockTest->skill === 'writing' && $attempts->first())
            <a href="{{ route('writingHistory.show', $attempts->first()->id) }}"
               class="flex-1 text-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition-colors shadow-md flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                Xem chi tiết đánh giá
            </a>
            <a href="{{ route('mock-test.create', $mockTest->skill) }}"
               class="flex-1 text-center px-6 py-3 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors shadow-sm">
                🔄 Thi lại
            </a>
        @else
            <a href="{{ route('mock-test.create', $mockTest->skill) }}"
               class="flex-1 text-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors shadow-md">
                🔄 Thi lại
            </a>
            <a href="{{ route('history.index') }}"
               class="flex-1 text-center px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                📋 Xem lịch sử
            </a>
        @endif
        <a href="{{ route('skills.show', $mockTest->skill) }}"
           class="flex-1 text-center px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
            ← Quay lại Skill
        </a>
    </div>
</div>
@endsection
