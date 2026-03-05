@extends('layouts.admin')

@section('title', 'Chấm bài Writing - ' . ($attempt->user->name ?? 'Student'))

@section('content')
<div class="space-y-6 pb-24 max-w-7xl mx-auto">

    {{-- Top Header Area --}}
    <div>
        <a href="{{ route('admin.writing-reviews.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Quay lại danh sách
        </a>
        
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Chấm bài: <span class="text-indigo-600">{{ $attempt->user->name ?? 'Student' }}</span>
                </h1>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        Set đề: <strong class="text-gray-900">{{ $attempt->set->title ?? '—' }}</strong>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Nộp lúc: <span class="text-gray-900">{{ $attempt->created_at->format('H:i - d/m/Y') }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Progress Badge --}}
            <div class="shrink-0">
                @php
                    $allGraded = $attempt->attemptAnswers->every(fn($a) => $a->grading_status === 'graded');
                    $gradedCount = $attempt->attemptAnswers->where('grading_status', 'graded')->count();
                    $totalCount = $attempt->attemptAnswers->count();
                @endphp
                @if($allGraded)
                    <div class="inline-flex items-center gap-2 bg-green-50 border border-green-200 px-4 py-2 rounded-lg">
                        <span class="text-green-600">✅</span>
                        <span class="text-sm font-semibold text-green-700">Đã chấm xong</span>
                    </div>
                @else
                    <div class="inline-flex items-center gap-3 bg-amber-50 border border-amber-200 px-4 py-2 rounded-lg">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                        </span>
                        <div class="flex flex-col">
                            <span class="text-xs text-amber-600 font-medium">Tiến độ chấm</span>
                            <span class="text-sm font-bold text-amber-800">{{ $gradedCount }} / {{ $totalCount }} phần</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Main Grading Form --}}
    <form action="{{ route('admin.writing-reviews.grade', $attempt->id) }}" method="POST" class="space-y-8">
        @csrf

        @foreach($attempt->attemptAnswers as $loopIdx => $answer)
            @php
                $q   = $answer->question;
                $ans = $answer->answer;
                $partNum = $q->part;
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                
                {{-- Header of the Part --}}
                <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4 bg-gray-50">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white font-bold text-lg flex items-center justify-center shadow-sm">
                            {{ $partNum }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ $q->title ?? 'Writing Part ' . $partNum }}</h2>
                            <p class="text-sm text-gray-500">{{ ['1' => 'Từ vựng & Mẫu câu', '2' => 'Email cá nhân (Ngắn)', '3' => 'Tương tác mạng xã hội', '4' => 'Thư thân mật & Thư trang trọng'][$partNum] ?? '' }}</p>
                        </div>
                    </div>
                    <div>
                        @if($answer->grading_status === 'graded')
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-green-100/50 border border-green-200 rounded-lg">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-sm font-semibold text-green-700">Điểm: {{ $answer->writingReview->total_score ?? 0 }}/10</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 border border-gray-200 rounded-lg">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                <span class="text-sm font-medium text-gray-600">Chờ chấm</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 2-Column Layout --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">

                    {{-- ================= LEFT COLUMN ================= --}}
                    <div class="p-6 space-y-6">
                        {{-- Prompt --}}
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm tracking-wide mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                NỘI DUNG ĐỀ BÀI
                            </h3>
                            <div class="bg-gray-50 rounded-xl p-5 text-sm text-gray-800 space-y-3 border border-gray-100">
                                @if($q->stem)
                                    <p class="font-semibold text-gray-900">{{ $q->stem }}</p>
                                @endif
                                @if(is_string($q->metadata['scenario'] ?? null))
                                    <div class="bg-white border-l-2 border-indigo-400 p-3 italic text-gray-600 shadow-sm">
                                        {{ $q->metadata['scenario'] }}
                                    </div>
                                @endif
                                @if(is_string($q->metadata['topic'] ?? null))
                                    <p class="inline-flex py-1 px-2.5 bg-white border border-gray-200 rounded-md text-xs font-medium text-gray-600">
                                        Topic: {{ $q->metadata['topic'] }}
                                    </p>
                                @endif
                                @if(is_string($q->metadata['instructions'] ?? null))
                                    <p class="text-gray-600">{{ $q->metadata['instructions'] }}</p>
                                @endif
                                
                                {{-- Part 4 specific sub-prompts --}}
                                @if($partNum == 4)
                                    @php
                                        $t1 = is_array($q->metadata['task1'] ?? null) ? $q->metadata['task1'] : [];
                                        $t2 = is_array($q->metadata['task2'] ?? null) ? $q->metadata['task2'] : [];
                                    @endphp
                                    <div class="pt-4 mt-2 border-t border-gray-200 space-y-4">
                                        @if($t1)
                                            <div>
                                                <div class="font-bold text-gray-900 text-xs mb-1 flex items-center gap-2">
                                                    Task 1 (Informal)
                                                    @if(isset($t1['word_limit']) && is_scalar($t1['word_limit']))
                                                        <span class="text-gray-500 font-normal">~{{ $t1['word_limit'] }} từ</span>
                                                    @endif
                                                </div>
                                                @if(is_string($t1['prompt'] ?? null)) <p class="text-gray-600">{{ $t1['prompt'] }}</p> @endif
                                            </div>
                                        @endif
                                        @if($t2)
                                            <div>
                                                <div class="font-bold text-gray-900 text-xs mb-1 flex items-center gap-2">
                                                    Task 2 (Formal)
                                                    @if(isset($t2['word_limit']) && is_scalar($t2['word_limit']))
                                                        <span class="text-gray-500 font-normal">~{{ $t2['word_limit'] }} từ</span>
                                                    @endif
                                                </div>
                                                @if(is_string($t2['prompt'] ?? null)) <p class="text-gray-600">{{ $t2['prompt'] }}</p> @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- AI & Sample Answers Container --}}
                        <div class="space-y-3">
                            {{-- Sample Answers --}}
                            @php
                                $sampleAnswers = [];
                                if ($partNum == 1 || $partNum == 2) {
                                    if (!empty($q->metadata['sample_answer'])) $sampleAnswers[] = ['label' => 'Đáp án mẫu', 'content' => $q->metadata['sample_answer']];
                                } elseif ($partNum == 3) {
                                    foreach ($q->metadata['questions'] ?? [] as $idx => $pq) {
                                        if (!empty($pq['sample_answer'])) $sampleAnswers[] = ['label' => 'Câu ' . ($idx + 1), 'content' => $pq['sample_answer']];
                                    }
                                } elseif ($partNum == 4) {
                                    if (!empty($q->metadata['task1']['sample_answer'])) $sampleAnswers[] = ['label' => 'Task 1 (Informal)', 'content' => $q->metadata['task1']['sample_answer']];
                                    if (!empty($q->metadata['task2']['sample_answer'])) $sampleAnswers[] = ['label' => 'Task 2 (Formal)', 'content' => $q->metadata['task2']['sample_answer']];
                                }
                            @endphp

                            @if(!empty($sampleAnswers))
                                <div x-data="{ openSample: true }" class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                    <button type="button" @click="openSample = !openSample" class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                            <span class="font-medium text-gray-900 text-sm">Đáp án mẫu tham khảo</span>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openSample }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="openSample" x-collapse x-cloak>
                                        <div class="p-4 bg-gray-50 border-t border-gray-100 space-y-4 text-sm">
                                            @foreach($sampleAnswers as $sample)
                                                <div>
                                                    @if(count($sampleAnswers) > 1)
                                                        <div class="font-bold text-gray-700 mb-1">{{ $sample['label'] }}</div>
                                                    @endif
                                                    <div class="text-gray-600 whitespace-pre-wrap">{{ trim($sample['content']) }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- AI Feedback --}}
                            @php
                                $hasAiFeedback = !empty($answer->ai_metadata['feedback']);
                                $aiFeedback = $answer->ai_metadata['feedback'] ?? null;
                                $schemaVersion = $aiFeedback['schema_version'] ?? 3;
                            @endphp

                            @if($hasAiFeedback)
                                <div x-data="{ openAi: false }" class="bg-white border border-indigo-200 rounded-xl overflow-hidden shadow-sm mt-3">
                                    <button type="button" @click="openAi = !openAi" class="w-full bg-indigo-50/80 px-4 py-3 flex items-center justify-between text-indigo-900 hover:bg-indigo-100 transition-colors border-b border-indigo-100/50">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                                            <span class="font-bold text-indigo-900 text-sm">Góp ý tự động từ AI</span>
                                        </div>
                                        <svg class="w-4 h-4 text-indigo-500 transition-transform duration-200" :class="{ 'rotate-180': openAi }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="openAi" x-collapse x-cloak>
                                        <div class="p-4 bg-white space-y-5 text-sm">
                                            {{-- Scores grid --}}
                                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                                                @foreach(['grammar', 'vocabulary', 'coherence', 'task_fulfillment'] as $c)
                                                    @if(isset($aiFeedback['scores'][$c]))
                                                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                                                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $c) }}</div>
                                                            <div class="text-base font-black text-indigo-600">{{ $aiFeedback['scores'][$c] }}/5</div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            {{-- Detailed Feedback --}}
                                            @if($schemaVersion >= 3 && !empty($aiFeedback['part_responses']))
                                                <div class="space-y-4">
                                                    @foreach($aiFeedback['part_responses'] as $idx => $response)
                                                        <div class="relative bg-white rounded-lg p-4 border border-indigo-100 shadow-sm mt-2">
                                                            <div class="absolute -top-3 left-4 bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded-md">
                                                                {{ $response['label'] ?? 'Phần ' . ($idx + 1) }}
                                                            </div>
                                                            
                                                            <div class="mt-2 text-sm">
                                                                @if(!empty($response['detailed_corrections']))
                                                                    <div class="space-y-3 mb-4">
                                                                        <div class="text-xs font-bold text-gray-500 uppercase">Sửa lỗi:</div>
                                                                        @foreach($response['detailed_corrections'] as $correction)
                                                                            <div class="bg-gray-50 rounded p-3 border border-gray-200">
                                                                                <div class="line-through text-red-400 font-medium mb-1">{{ $correction['original'] ?? '' }}</div>
                                                                                <div class="text-green-600 font-bold mb-1">{{ $correction['corrected'] ?? '' }}</div>
                                                                                <div class="text-xs text-slate-500 italic bg-white p-2 border border-slate-100 rounded">{{ $correction['explanation'] ?? '' }}</div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @endif

                                                                @if(!empty($response['improved_sample']))
                                                                    <div>
                                                                        <div class="text-xs font-bold text-gray-500 uppercase mb-2 mt-4 pt-4 border-t border-gray-100">Bài mẫu tham khảo:</div>
                                                                        <div class="text-sm text-indigo-900 bg-indigo-50 p-4 rounded-lg font-medium leading-relaxed whitespace-pre-wrap">{{ $response['improved_sample'] }}</div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            {{-- General Suggestions --}}
                                            @if(!empty($aiFeedback['suggestions']))
                                                <div class="mt-4 bg-amber-50 p-4 rounded-lg border border-amber-100">
                                                    <div class="text-sm font-bold text-amber-800 mb-2 flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                        Lời khuyên chung
                                                    </div>
                                                    <ul class="list-disc pl-5 text-amber-900 space-y-1 text-xs">
                                                        @foreach($aiFeedback['suggestions'] as $suggestion)
                                                            <li>{{ $suggestion }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="bg-gray-50 border border-gray-200 border-dashed rounded-xl p-5 mt-3 text-center">
                                    <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <p class="text-sm font-medium text-gray-500">Chưa có dữ liệu chấm điểm từ AI cho câu hỏi này</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- ================= RIGHT COLUMN ================= --}}
                    <div class="p-6 flex flex-col gap-6 bg-white">

                        {{-- Student Answer Section --}}
                        <div class="">
                            <h3 class="font-bold text-gray-900 text-sm tracking-wide mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                BÀI LÀM CỦA HỌC SINH
                            </h3>

                            @if($partNum == 4)
                                @php
                                    $t1Txt = is_array($ans) ? (is_string($ans['task1'] ?? null) ? $ans['task1'] : (is_string($ans[0] ?? null) ? $ans[0] : '')) : '';
                                    $t2Txt = is_array($ans) ? (is_string($ans['task2'] ?? null) ? $ans['task2'] : (is_string($ans[1] ?? null) ? $ans[1] : '')) : (is_string($ans) ? $ans : '');
                                    $t1Prompt = $q->metadata['task1']['prompt'] ?? $q->metadata['task1']['instruction'] ?? '';
                                    $t2Prompt = $q->metadata['task2']['prompt'] ?? $q->metadata['task2']['instruction'] ?? '';
                                @endphp
                                <div class="space-y-4">
                                    {{-- Task 1 --}}
                                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                                        @if($t1Prompt)
                                        <div class="bg-blue-50 px-4 py-3 border-b border-blue-100">
                                            <span class="text-sm font-semibold text-blue-900 block mb-1">Task 1 (Informal):</span>
                                            <span class="text-sm text-blue-800 italic leading-snug">{{ $t1Prompt }}</span>
                                        </div>
                                        @endif
                                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-700">{{ $t1Prompt ? 'Bài làm:' : 'Task 1 (Informal)' }}</span>
                                            <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200">{{ str_word_count($t1Txt) }} từ</span>
                                        </div>
                                        <div class="p-4 text-sm text-gray-800 whitespace-pre-wrap min-h-[120px]">{{ $t1Txt ? trim($t1Txt) : '(Bỏ trống)' }}</div>
                                    </div>
                                    {{-- Task 2 --}}
                                    <div class="rounded-xl border border-gray-200 overflow-hidden">
                                        @if($t2Prompt)
                                        <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100">
                                            <span class="text-sm font-semibold text-indigo-900 block mb-1">Task 2 (Formal):</span>
                                            <span class="text-sm text-indigo-800 italic leading-snug">{{ $t2Prompt }}</span>
                                        </div>
                                        @endif
                                        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                            <span class="text-sm font-semibold text-gray-700">{{ $t2Prompt ? 'Bài làm:' : 'Task 2 (Formal)' }}</span>
                                            <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200">{{ str_word_count($t2Txt) }} từ</span>
                                        </div>
                                        <div class="p-4 text-sm text-gray-800 whitespace-pre-wrap min-h-[120px]">{{ $t2Txt ? trim($t2Txt) : '(Bỏ trống)' }}</div>
                                    </div>
                                </div>

                            @elseif($partNum == 3)
                                @php $subQs = $q->metadata['questions'] ?? []; @endphp
                                <div class="space-y-4">
                                    @php
                                        // Standard functional colors for sub-questions
                                        $colMap = [
                                            0 => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700'],
                                            1 => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700'],
                                            2 => ['bg' => 'bg-pink-50', 'text' => 'text-pink-700'],
                                            3 => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700'],
                                        ];
                                    @endphp
                                    @foreach($subQs as $sqIdx => $sq)
                                        @php
                                            $sqAns  = is_array($ans) ? (is_string($ans[$sqIdx] ?? null) ? $ans[$sqIdx] : '') : '';
                                            $sqQTxt = is_array($sq) ? ($sq['prompt'] ?? $sq['question'] ?? '') : (is_string($sq) ? $sq : '');
                                            $cm     = $colMap[$sqIdx % count($colMap)];
                                        @endphp
                                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                                            <div class="bg-gray-50 px-4 py-2.5 border-b border-gray-200 flex items-start gap-3">
                                                <span class="w-6 h-6 rounded-md {{ $cm['bg'] }} {{ $cm['text'] }} text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">{{ $sqIdx + 1 }}</span>
                                                <span class="text-sm font-medium text-gray-800 flex-1 leading-snug">{{ $sqQTxt ?: 'Câu ' . ($sqIdx + 1) }}</span>
                                                <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200 shrink-0">{{ str_word_count($sqAns ?? '') }} từ</span>
                                            </div>
                                            <div class="p-4 text-sm text-gray-800 whitespace-pre-wrap min-h-[80px]">{{ $sqAns ? trim($sqAns) : '(Bỏ trống)' }}</div>
                                        </div>
                                    @endforeach
                                </div>

                            @elseif($partNum == 2)
                                @php 
                                    $p2Text = is_string($ans) ? $ans : (is_array($ans) ? implode("\n", array_filter($ans, 'is_string')) : ''); 
                                    $scenario = $q->metadata['scenario'] ?? $q->stem ?? '';
                                @endphp
                                <div class="rounded-xl border border-gray-200 overflow-hidden">
                                    @if($scenario)
                                    <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100">
                                        <span class="text-sm font-semibold text-indigo-900 block mb-1">Yêu cầu / Tình huống:</span>
                                        <span class="text-sm text-indigo-800 italic leading-snug">{{ $scenario }}</span>
                                    </div>
                                    @endif
                                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                                        <span class="text-sm font-semibold text-gray-700">Bài làm:</span>
                                        <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200">{{ str_word_count($p2Text ?? '') }} từ</span>
                                    </div>
                                    <div class="p-4 text-sm text-gray-800 whitespace-pre-wrap min-h-[120px]">{{ $p2Text ? trim($p2Text) : '(Bỏ trống)' }}</div>
                                </div>

                            @elseif($partNum == 1)
                                @php $fields = $q->metadata['fields'] ?? []; @endphp
                                <div class="space-y-4">
                                    @for($i = 0; $i < 5; $i++)
                                        @php
                                            $fieldAns = is_array($ans) ? ($ans[$i] ?? '') : '';
                                            $fieldLabel = $fields[$i]['label'] ?? 'Câu ' . ($i + 1);
                                        @endphp
                                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                                            <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center gap-3">
                                                <span class="text-sm font-medium text-gray-800 flex-1 leading-snug"><span class="font-bold text-gray-500 mr-1">{{ $i+1 }}.</span> {{ $fieldLabel }}</span>
                                                <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-200 shrink-0">{{ str_word_count($fieldAns) }} từ</span>
                                            </div>
                                            <div class="p-3 text-sm text-gray-800 whitespace-pre-wrap font-medium">{{ $fieldAns ? trim($fieldAns) : '(Bỏ trống)' }}</div>
                                        </div>
                                    @endfor
                                </div>
                            @endif
                        </div>

                        {{-- Grading Form Area --}}
                        <div class="bg-indigo-50/30 rounded-xl border border-indigo-100 p-5 shrink-0"
                             x-data="{ score: {{ $answer->writingReview->total_score ?? 0 }} }">
                             
                            <div class="space-y-4">
                                {{-- Score Input --}}
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="text-sm font-bold text-gray-900">Điểm số</label>
                                        <div class="flex items-center gap-1.5 bg-white px-2 py-1 rounded-lg border border-gray-200 shadow-sm">
                                            <input type="number" step="0.5" min="0" max="10" x-model="score"
                                                class="w-14 px-1 text-center bg-transparent border-none focus:ring-0 font-bold text-indigo-600 text-lg p-0 h-auto">
                                            <span class="text-sm text-gray-400">/10</span>
                                        </div>
                                    </div>
                                    <input type="range" name="scores[{{ $answer->id }}]" min="0" max="10" step="0.5"
                                        x-model="score"
                                        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                </div>

                                {{-- Quick Preset Buttons --}}
                                <div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach([0, 2, 4, 5, 6, 7, 8, 9, 10] as $preset)
                                            <button type="button" @click="score = {{ $preset }}"
                                                :class="score == {{ $preset }} ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                                                class="w-8 h-8 rounded-md border text-sm font-medium transition-colors flex items-center justify-center">
                                                {{ $preset }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Feedback TextBox --}}
                                <div>
                                    <label class="text-sm font-bold text-gray-900 mb-2 block">Nhận xét của giáo viên</label>
                                    <textarea name="comments[{{ $answer->id }}]" rows="3" id="comment-{{ $answer->id }}"
                                        class="ckeditor w-full px-3 py-2 text-sm text-gray-800 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 resize-y bg-white"
                                        placeholder="Ghi chú lỗi sai, khen ngợi...">{{ $answer->writingReview->comment ?? '' }}</textarea>
                                </div>
                                
                                @if($answer->writingReview)
                                    <div class="pt-2">
                                        <p class="text-xs text-gray-500 text-right">
                                            Chấm bởi <strong class="text-gray-700">{{ $answer->writingReview->reviewer->name ?? 'Admin' }}</strong> · {{ $answer->writingReview->updated_at->format('d/m/Y H:i') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Fixed Bottom Submit Bar --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgb(0,0,0,0.05)] z-40">
            <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="font-bold text-gray-900">{{ $attempt->user->name ?? 'Student' }}</p>
                    <p class="text-sm text-gray-500">Writing Mock Test</p>
                </div>
                
                <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition-colors flex items-center gap-2">
                    Lưu điểm toàn bài
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
            </div>
        </div>
    </form>

    {{-- Success Toast --}}
    @if(session('success'))
        <div class="fixed bottom-24 right-6 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2"
            x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
            x-transition>
            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-sm font-medium">{{ session('success') }}</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<style>
    /* Restore Tailwind's reset styles for CKEditor content */
    .ck-content strong { font-weight: bold; }
    .ck-content em { font-style: italic; }
    .ck-content u { text-decoration: underline; }
    .ck-content s { text-decoration: line-through; }
    .ck-content ul { list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
    .ck-content ol { list-style-type: decimal; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
    .ck-content h1 { font-size: 2em; font-weight: bold; margin-top: 0.67em; margin-bottom: 0.67em; }
    .ck-content h2 { font-size: 1.5em; font-weight: bold; margin-top: 0.83em; margin-bottom: 0.83em; }
    .ck-content h3 { font-size: 1.17em; font-weight: bold; margin-top: 1em; margin-bottom: 1em; }
    .ck-content p { margin-bottom: 0.5em; }
    .ck-editor__editable_inline { min-height: 120px; }
</style>

<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof ClassicEditor !== 'undefined') {
            document.querySelectorAll('textarea.ckeditor').forEach((textarea) => {
                ClassicEditor.create(textarea, {
                    toolbar: [
                        'heading', '|',
                        'bold', 'italic', 'strikethrough', '|',
                        'bulletedList', 'numberedList', '|',
                        'undo', 'redo'
                    ],
                    language: 'vi'
                }).catch(error => {
                    console.error(error);
                });
            });
        } else {
            console.error('CKEditor is not loaded!');
        }
    });
</script>
@endpush
