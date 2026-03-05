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

                            {{-- Part Specific Layouts --}}
                            @if($attempt->skill === 'listening')
                                @if($q->part == 1)
                                    {{-- Listening P1: Multiple Choice with optional audio --}}
                                    @php
                                        $choices   = $q->metadata['choices'] ?? [];
                                        $cIdx      = $q->metadata['correct_answer'] ?? null;
                                        $uIdx      = $userAns;
                                        $audioPath = $q->metadata['audio'] ?? $q->audio_path ?? null;
                                    @endphp
                                    @if($audioPath)
                                        <div class="mb-2">
                                            <audio src="{{ asset('storage/' . $audioPath) }}" controls class="w-full h-8"></audio>
                                        </div>
                                    @endif
                                    @if($q->stem)
                                        <p class="text-sm font-medium text-gray-800 mb-2">{{ $q->stem }}</p>
                                    @endif
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5 mb-2">
                                        @foreach($choices as $cIdxLoop => $cText)
                                            @php
                                                $isUser    = ($uIdx !== null && $uIdx == $cIdxLoop);
                                                $isCorrect = ($cIdx !== null && $cIdx == $cIdxLoop);
                                                $cls = 'bg-white border-gray-100';
                                                if ($isUser && $isCorrect)  $cls = 'bg-green-50 border-green-200 text-green-700 font-bold';
                                                elseif ($isUser)            $cls = 'bg-red-50 border-red-200 text-red-700 font-bold';
                                                elseif ($isCorrect)         $cls = 'bg-green-50 border-green-200 text-green-700 font-bold opacity-80';
                                            @endphp
                                            <div class="px-3 py-2 rounded-lg border text-xs flex items-center gap-2 {{ $cls }}">
                                                <span class="w-5 h-5 rounded-full border border-current flex items-center justify-center text-[10px] shrink-0">{{ chr(65 + $cIdxLoop) }}</span>
                                                <span>{{ $cText }}</span>
                                            </div>
                                        @endforeach
                                    </div>

                                @elseif($q->part == 2)
                                    {{-- Listening P2: Speaker Matching --}}
                                    @php
                                        $items       = $q->metadata['items'] ?? [];
                                        $choices     = $q->metadata['choices'] ?? [];
                                        $correctAns  = $q->metadata['correct_answers'] ?? [];
                                        $audioFiles  = $q->metadata['audio_files'] ?? [];
                                        $userAnswers = is_array($userAns) ? $userAns : [];
                                    @endphp
                                    @if($q->metadata['topic'] ?? $q->stem)
                                        <p class="text-xs font-semibold text-indigo-700 mb-2">{{ $q->metadata['topic'] ?? $q->stem }}</p>
                                    @endif
                                    <div class="space-y-2 mb-2">
                                        @foreach($items as $sIdx => $speakerName)
                                            @php
                                                $uA        = $userAnswers[$sIdx] ?? null;
                                                $cA        = $correctAns[$sIdx] ?? null;
                                                $isOk      = ($uA !== null && $uA == $cA);
                                                $uText     = ($uA !== null && isset($choices[$uA])) ? $choices[$uA] : '(Bỏ trống)';
                                                $cText     = isset($choices[$cA]) ? $choices[$cA] : 'N/A';
                                                $audio     = $audioFiles[$sIdx] ?? null;
                                            @endphp
                                            <div class="p-2.5 rounded-lg border text-xs {{ $isOk ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="font-bold text-gray-700">{{ $speakerName }}</span>
                                                    @if($audio)
                                                        <audio src="{{ asset('storage/' . $audio) }}" controls class="h-6 flex-1"></audio>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-gray-500">Bạn chọn:</span>
                                                    <span class="font-bold {{ $isOk ? 'text-green-600' : 'text-red-600' }}">{{ $uText }}</span>
                                                    @if(!$isOk)
                                                        <span class="text-gray-400">|</span>
                                                        <span class="text-green-600 font-bold">Đúng: {{ $cText }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                @elseif($q->part == 3)
                                    {{-- Listening P3: Man/Woman/Both Multi-Matching --}}
                                    @php
                                        $statements  = $q->metadata['statements'] ?? [];
                                        $sharedOpts  = $q->metadata['shared_choices'] ?? [];
                                        $correctAns  = $q->metadata['correct_answers'] ?? [];
                                        $userAnswers = is_array($userAns) ? $userAns : [];
                                        $audioPath   = $q->audio_path ?? null;
                                    @endphp
                                    @if($audioPath)
                                        <div class="mb-2">
                                            <audio src="{{ asset('storage/' . $audioPath) }}" controls class="w-full h-8"></audio>
                                        </div>
                                    @endif
                                    @if($q->metadata['topic'] ?? $q->stem)
                                        <p class="text-xs font-semibold text-indigo-700 mb-2">{{ $q->metadata['topic'] ?? $q->stem }}</p>
                                    @endif
                                    <div class="space-y-1.5 mb-2">
                                        @foreach($statements as $stIdx => $stText)
                                            @php
                                                $uA   = $userAnswers[$stIdx] ?? null;
                                                $cA   = $correctAns[$stIdx] ?? null;
                                                $isOk = ($uA !== null && $uA == $cA);
                                                $uLabel = isset($sharedOpts[$uA]) ? $sharedOpts[$uA] : '(Bỏ trống)';
                                                $cLabel = isset($sharedOpts[$cA]) ? $sharedOpts[$cA] : 'N/A';
                                            @endphp
                                            <div class="flex items-start gap-2 p-2 rounded border text-xs {{ $isOk ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <div class="flex-1"><span class="font-medium text-gray-800">{{ $stIdx + 1 }}. {{ $stText }}</span></div>
                                                <div class="shrink-0 text-right">
                                                    <span class="font-bold {{ $isOk ? 'text-green-600' : 'text-red-600' }}">{{ $uLabel }}</span>
                                                    @if(!$isOk)
                                                        <p class="text-[10px] text-green-600 font-bold">✓ {{ $cLabel }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                @elseif($q->part == 4)
                                    {{-- Listening P4: Multiple sub-questions (single choice each) --}}
                                    @php
                                        $subQuestions = $q->metadata['questions'] ?? [];
                                        $userAnswers  = is_array($userAns) ? $userAns : [];
                                        $audioPath    = $q->audio_path ?? null;
                                    @endphp
                                    @if($audioPath)
                                        <div class="mb-2">
                                            <audio src="{{ asset('storage/' . $audioPath) }}" controls class="w-full h-8"></audio>
                                        </div>
                                    @endif
                                    <div class="space-y-2 mb-2">
                                        @foreach($subQuestions as $sqIdx => $sq)
                                            @php
                                                $sqChoices  = $sq['choices'] ?? [];
                                                $sqCorrect  = $sq['correct_answer'] ?? null;
                                                $sqUser     = $userAnswers[$sqIdx] ?? null;
                                                $sqIsOk     = ($sqUser !== null && $sqUser == $sqCorrect);
                                                $sqAudio    = $sq['audio'] ?? null;
                                            @endphp
                                            <div class="p-2.5 rounded-lg border text-xs {{ $sqIsOk ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                @if($sqAudio)
                                                    <audio src="{{ asset('storage/' . $sqAudio) }}" controls class="w-full h-7 mb-1"></audio>
                                                @endif
                                                <p class="font-medium text-gray-800 mb-1.5">{{ $sqIdx + 1 }}. {{ $sq['question'] ?? '' }}</p>
                                                <div class="flex flex-wrap gap-1.5">
                                                    @foreach($sqChoices as $cKey => $cVal)
                                                        @php
                                                            $isU = ($sqUser !== null && $sqUser == $cKey);
                                                            $isC = ($sqCorrect !== null && $sqCorrect == $cKey);
                                                            $cls = 'border-gray-200 text-gray-600';
                                                            if ($isU && $isC)   $cls = 'bg-green-100 border-green-300 text-green-700 font-bold';
                                                            elseif ($isU)       $cls = 'bg-red-100 border-red-300 text-red-700 font-bold';
                                                            elseif ($isC)       $cls = 'bg-green-50 border-green-200 text-green-600 opacity-80';
                                                        @endphp
                                                        <span class="px-2 py-0.5 rounded border {{ $cls }}">{{ chr(65 + $cKey) }}. {{ $cVal }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                            @elseif($attempt->skill === 'reading')
                                @if($q->part == 1)
                                    <div class="text-sm text-gray-700 mb-2 p-3 bg-gray-50 rounded-lg border border-gray-100 italic">
                                        @php
                                            $paragraphs = $q->metadata['paragraphs'] ?? [];
                                            $correctAnswers = $q->metadata['correct_answers'] ?? [];
                                            $choices = $q->metadata['choices'] ?? [];
                                            $userAnswers = is_array($userAns) ? $userAns : [];
                                        @endphp
                                        @foreach($paragraphs as $pIdx => $para)
                                            <div class="mb-2 last:mb-0">
                                                @php
                                                    $segments = explode('[BLANK]', $para);
                                                    $uAnsIdx = $userAnswers[$pIdx] ?? null;
                                                    $cAnsIdx = $correctAnswers[$pIdx] ?? null;
                                                    $uAnsText = ($uAnsIdx !== null && isset($choices[$pIdx][$uAnsIdx])) ? $choices[$pIdx][$uAnsIdx] : '(Bỏ trống)';
                                                    $cAnsText = (isset($choices[$pIdx][$cAnsIdx])) ? $choices[$pIdx][$cAnsIdx] : 'N/A';
                                                    $isParaCorrect = ($uAnsIdx == $cAnsIdx);
                                                @endphp
                                                @foreach($segments as $sIdx => $segment)
                                                    {{ $segment }}
                                                    @if($sIdx < count($segments) - 1)
                                                        <span class="font-bold border-b {{ $isParaCorrect ? 'text-green-600 border-green-200' : 'text-red-600 border-red-200' }}">
                                                            {{ $uAnsText }}
                                                        </span>
                                                        @if(!$isParaCorrect)
                                                        <span class="text-[11px] text-green-600 ml-0.5">({{ $cAnsText }})</span>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($q->part == 2)
                                    <div class="space-y-1 mb-3">
                                        @php
                                            $sentences = $q->metadata['sentences'] ?? [];
                                            $userOrder = is_array($userAns) ? $userAns : [];
                                        @endphp
                                        <div class="flex items-start gap-4 p-2 bg-gray-50 rounded border border-gray-100 text-sm">
                                            <span class="font-bold text-gray-400 w-4 shrink-0">1</span>
                                            <span class="text-gray-600 truncate">{{ $sentences[0] ?? '' }}</span>
                                            <span class="ml-auto text-[11px] text-green-600 font-bold uppercase">Cố định</span>
                                        </div>
                                        @foreach($userOrder as $idx => $slot)
                                            @php
                                                $slotIdx = $idx + 1;
                                                $expectedSentence = $sentences[$slotIdx] ?? 'N/A';
                                                $providedSentence = is_array($slot) ? ($slot['text'] ?? 'N/A') : ($slot ?? 'N/A');
                                                $isSlotCorrect = ($providedSentence === $expectedSentence);
                                            @endphp
                                            <div class="flex items-start gap-4 p-2 rounded border text-sm {{ $isSlotCorrect ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <span class="font-bold {{ $isSlotCorrect ? 'text-green-600' : 'text-red-500' }} w-4 shrink-0">{{ $slotIdx + 1 }}</span>
                                                <div class="flex-1 min-w-0">
                                                    <p class="{{ $isSlotCorrect ? 'text-green-800' : 'text-red-800' }}">{{ $providedSentence }}</p>
                                                    @if(!$isSlotCorrect)
                                                        <p class="text-[11px] text-green-600 mt-0.5"><span class="font-bold">Đúng:</span> {{ $expectedSentence }}</p>
                                                    @endif
                                                </div>
                                                @if($isSlotCorrect)
                                                    <svg class="w-3 h-3 text-green-500 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                @else
                                                    <svg class="w-3 h-3 text-red-400 ml-auto shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($q->part == 3)
                                    <div class="space-y-3 mb-3">
                                        @php
                                            $questions = $q->metadata['questions'] ?? [];
                                            $correctAns = $q->metadata['correct_answers'] ?? [];
                                            $userAnswers = is_array($userAns) ? $userAns : [];
                                        @endphp
                                        @foreach($questions as $qIdx => $qText)
                                            <div class="p-3 rounded-lg border {{ ($userAnswers[$qIdx] ?? null) == ($correctAns[$qIdx] ?? null) ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <p class="text-sm font-medium text-gray-800 mb-2">{{ $qIdx + 1 }}. {{ $qText }}</p>
                                                <div class="flex items-center gap-2 text-[11px]">
                                                    <span class="text-gray-500">Bạn chọn:</span>
                                                    <span class="font-bold {{ ($userAnswers[$qIdx] ?? null) == ($correctAns[$qIdx] ?? null) ? 'text-green-600' : 'text-red-600' }}">
                                                        Person {{ (isset($userAnswers[$qIdx]) && $userAnswers[$qIdx] !== '') ? chr(65 + (int)$userAnswers[$qIdx]) : '—' }}
                                                    </span>
                                                    @if(($userAnswers[$qIdx] ?? null) != ($correctAns[$qIdx] ?? null))
                                                        <span class="text-gray-400">|</span>
                                                        <span class="text-green-600 font-bold">Đúng: Person {{ isset($correctAns[$qIdx]) ? chr(65 + (int)$correctAns[$qIdx]) : 'N/A' }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($q->part == 4)
                                    <div class="space-y-4 mb-3">
                                        @php
                                            $paragraphs = $q->metadata['paragraphs'] ?? [];
                                            $headings = $q->metadata['headings'] ?? [];
                                            $correctAnswers = $q->metadata['correct_answers'] ?? [];
                                            $userAnswers = is_array($userAns) ? $userAns : [];
                                        @endphp
                                        @foreach($paragraphs as $pIdx => $pText)
                                            <div class="p-3 rounded-lg border {{ ($userAnswers[$pIdx] ?? null) == ($correctAnswers[$pIdx] ?? null) ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 rounded text-[11px] font-bold">Đoạn {{ $pIdx + 1 }}</span>
                                                    <span class="text-[11px] font-medium {{ ($userAnswers[$pIdx] ?? null) == ($correctAnswers[$pIdx] ?? null) ? 'text-green-600' : 'text-red-600' }}">
                                                        Tiêu đề: {{ (isset($userAnswers[$pIdx]) && $userAnswers[$pIdx] !== '' && isset($headings[$userAnswers[$pIdx]])) ? $headings[$userAnswers[$pIdx]] : '(Bỏ trống)' }}
                                                    </span>
                                                    @if(($userAnswers[$pIdx] ?? null) != ($correctAnswers[$pIdx] ?? null))
                                                        <span class="text-[11px] text-green-600 font-bold">(Đúng: {{ $headings[$correctAnswers[$pIdx]] ?? 'N/A' }})</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-600 italic line-clamp-2 leading-relaxed">{{ $pText }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @elseif($attempt->skill === 'grammar')
                                @if($q->part == 1)
                                    <div class="mb-3">
                                        <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm mb-2">
                                            <p class="text-gray-800 italic line-clamp-2">{!! $q->stem !!}</p>
                                        </div>
                                        @php
                                            $choices = $q->metadata['choices'] ?? ($q->metadata['options'] ?? []);
                                            $uIdx = $userAns;
                                            $cIdx = $correctAns;
                                        @endphp
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($choices as $cIdxLoop => $cItem)
                                                @php
                                                    $optId = is_array($cItem) ? ($cItem['id'] ?? chr(65 + $cIdxLoop)) : $cIdxLoop;
                                                    $optText = is_array($cItem) ? ($cItem['text'] ?? '') : $cItem;
                                                    $optLabel = is_array($cItem) ? ($cItem['id'] ?? chr(65 + $cIdxLoop)) : chr(65 + $cIdxLoop);
                                                    
                                                    $isUser = ($uIdx !== null && (string)$uIdx === (string)$optId);
                                                    $isCorrect = ($cIdx !== null && (string)$cIdx === (string)$optId);
                                                    
                                                    $choiceClass = 'bg-white border-gray-100';
                                                    if ($isUser && $isCorrect) $choiceClass = 'bg-green-50 border-green-200 text-green-700 font-bold';
                                                    elseif ($isUser) $choiceClass = 'bg-red-50 border-red-200 text-red-700 font-bold';
                                                    elseif ($isCorrect) $choiceClass = 'bg-green-50 border-green-200 text-green-700 font-bold opacity-80';
                                                @endphp
                                                <div class="px-3 py-2 rounded-lg border text-xs flex items-center gap-2 {{ $choiceClass }}">
                                                    <span class="w-5 h-5 rounded-full border border-current flex items-center justify-center text-[10px] shrink-0">
                                                        {{ $optLabel }}
                                                    </span>
                                                    <span>{{ $optText }}</span>
                                                    @if($isUser && $isCorrect)
                                                        <svg class="w-3 h-3 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                    @elseif($isUser)
                                                        <svg class="w-3 h-3 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif($q->part == 2)
                                    <div class="space-y-2 mb-3">
                                        @php
                                            $pairs = $q->metadata['pairs'] ?? [];
                                            $correctAnswers = $q->metadata['correct_answers'] ?? [];
                                            $userAnswers = is_array($userAns) ? $userAns : [];
                                        @endphp
                                        @foreach($pairs as $pair)
                                            @php
                                                $pid = $pair['id'];
                                                $uA = $userAnswers[$pid] ?? null;
                                                $cA = $correctAnswers[$pid] ?? null;
                                                $isCorrect = ($uA === $cA);
                                            @endphp
                                            <div class="flex items-center gap-2 p-2 rounded border text-[11px] {{ $isCorrect ? 'bg-green-50/50 border-green-100' : 'bg-red-50/50 border-red-100' }}">
                                                <span class="w-4 font-bold text-gray-400">{{ $pid }}</span>
                                                <span class="flex-1 text-gray-700">
                                                    {{ $pair['prompt'] ?? ($pair['prefix'] ?? '') . ' [___] ' . ($pair['suffix'] ?? '') }}
                                                </span>
                                                <div class="flex flex-col items-end shrink-0">
                                                    <span class="font-bold {{ $isCorrect ? 'text-green-600' : 'text-red-600' }}">{{ $uA ?: '—' }}</span>
                                                    @if(!$isCorrect)
                                                        <span class="text-[9px] text-green-600 font-bold italic">Đúng: {{ $cA }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                @if($q->stem)
                                    <p class="text-sm text-gray-700 mb-2 font-medium line-clamp-2">{{ $q->stem }}</p>
                                @endif
                            @endif

                            {{-- Skip redundant footer for parts that have detailed views --}}
                            @php
                                $hasDetailedView = ($attempt->skill === 'reading'   && in_array($q->part, [1, 2, 3, 4]))
                                                || ($attempt->skill === 'grammar'   && in_array($q->part, [1, 2]))
                                                || ($attempt->skill === 'listening' && in_array($q->part, [1, 2, 3, 4]));
                            @endphp

                            @if(!$hasDetailedView)
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
                            @endif

                            @if(!empty($q->explanation))
                                <div x-data="{ open: false }" class="mt-3">
                                    <button @click="open = !open" class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                                        <span>🔍 Xem giải thích / Transcript</span>
                                        <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-collapse x-cloak class="mt-2 p-3 bg-white border border-indigo-100 rounded-lg text-xs text-gray-600 leading-relaxed whitespace-pre-wrap">
                                        {!! $q->explanation !!}
                                    </div>
                                </div>
                            @endif
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
