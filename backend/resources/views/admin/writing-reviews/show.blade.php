@extends('layouts.admin')

@section('title', 'Chấm bài Writing - ' . ($attempt->user->name ?? 'Student'))

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.writing-reviews.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Quay lại danh sách
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Chấm bài: {{ $attempt->user->name ?? 'Student' }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Set: <strong>{{ $attempt->set->title ?? '—' }}</strong>
                · Nộp lúc: {{ $attempt->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>

    {{-- Overall Grading Form --}}
    <form action="{{ route('admin.writing-reviews.grade', $attempt->id) }}" method="POST" class="flex flex-col gap-10 pb-28">
        @csrf

        {{-- Each Writing Answer --}}
        @foreach($attempt->attemptAnswers as $answer)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Part Header --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">{{ $answer->question->title ?? 'Writing Part ' . $answer->question->part }}</h2>
                <div>
                    @if($answer->grading_status === 'graded')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            ✅ Đã chấm — {{ $answer->writingReview->total_score ?? 0 }}/10
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            ⏳ Chờ chấm
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col border-t border-gray-200">
                {{-- Top: Question + Student Answer --}}
                <div class="p-6 space-y-4">
                    {{-- Question Prompt --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Đề bài</h3>
                        <div class="bg-blue-50 rounded-lg p-4 text-sm text-gray-700">
                            <p class="font-medium mb-1">{{ $answer->question->stem }}</p>
                            @if($answer->question->metadata['scenario'] ?? false)
                                <p class="mt-2 italic text-gray-600">{{ $answer->question->metadata['scenario'] }}</p>
                            @endif
                            @if($answer->question->metadata['topic'] ?? false)
                                <p class="mt-2"><strong>Topic:</strong> {{ $answer->question->metadata['topic'] }}</p>
                            @endif
                            @if($answer->question->metadata['instructions'] ?? false)
                                <p class="mt-2 text-gray-600">{{ $answer->question->metadata['instructions'] }}</p>
                            @endif
                        </div>

                        {{-- Admin Sample Answer --}}
                        @php
                            $sampleAnswers = [];
                            $q = $answer->question;
                            if ($q->part == 1 || $q->part == 2) {
                                if (!empty($q->metadata['sample_answer'])) {
                                    $sampleAnswers[] = ['label' => 'Đáp án mẫu từ Admin', 'content' => $q->metadata['sample_answer']];
                                }
                            } elseif ($q->part == 3) {
                                foreach ($q->metadata['questions'] ?? [] as $idx => $pq) {
                                    if (!empty($pq['sample_answer'])) {
                                        $sampleAnswers[] = ['label' => 'Đáp án mẫu Câu ' . ($idx + 1), 'content' => $pq['sample_answer']];
                                    }
                                }
                            } elseif ($q->part == 4) {
                                if (!empty($q->metadata['task1']['sample_answer'])) {
                                    $sampleAnswers[] = ['label' => 'Đáp án mẫu Task 1 (Informal)', 'content' => $q->metadata['task1']['sample_answer']];
                                }
                                if (!empty($q->metadata['task2']['sample_answer'])) {
                                    $sampleAnswers[] = ['label' => 'Đáp án mẫu Task 2 (Formal)', 'content' => $q->metadata['task2']['sample_answer']];
                                }
                            }
                        @endphp

                        @if(!empty($sampleAnswers))
                            <div class="mt-4 border border-emerald-100 bg-emerald-50 rounded-lg overflow-hidden shadow-sm">
                                <div class="bg-emerald-100/50 px-4 py-2 flex items-center gap-2 font-bold text-xs text-emerald-800 border-b border-emerald-100">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span>Đáp án tham khảo chuẩn từ Admin</span>
                                </div>
                                <div class="p-4 space-y-3">
                                    @foreach($sampleAnswers as $sample)
                                        <div>
                                            <div class="text-[10px] font-bold text-gray-400 uppercase mb-1">{{ $sample['label'] }}</div>
                                            <div class="text-xs text-emerald-900 leading-relaxed whitespace-pre-wrap font-medium">{{ trim($sample['content']) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="mt-2 text-[10px] text-gray-400 italic">
                                * Không có đáp án mẫu cho phần này.
                            </div>
                        @endif
                    </div>

                    {{-- Student's Writing --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Bài viết của học sinh</h3>
                        <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-800 leading-relaxed border border-gray-200 min-h-[120px]">
                            @if(is_array($answer->answer))
                                @foreach($answer->answer as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $k => $v)
                                            <div class="mb-2">
                                                <span class="font-medium text-gray-500">{{ $k }}:</span>
                                                <span>{{ $v }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="mb-3">
                                            @if(is_numeric($key))
                                                <p>{{ $value }}</p>
                                            @else
                                                <p><span class="font-medium text-gray-500">{{ $key }}:</span> {{ $value }}</p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <p>{{ $answer->answer }}</p>
                            @endif
                        </div>
                        @php
                            $wordCount = is_array($answer->answer) 
                                ? str_word_count(implode(' ', array_map(fn($v) => is_array($v) ? implode(' ', $v) : $v, $answer->answer)))
                                : str_word_count($answer->answer ?? '');
                        @endphp
                        <p class="text-xs text-gray-400 mt-1">Số từ: ~{{ $wordCount }}</p>

                        {{-- AI Feedback Schema V3 Reference for Admin --}}
                        @if(!empty($answer->ai_metadata['feedback']))
                            @php
                                $aiFeedback = $answer->ai_metadata['feedback'];
                                $schemaVersion = $aiFeedback['schema_version'] ?? 1;
                            @endphp

                            <div class="mt-6 border border-indigo-100 rounded-xl overflow-hidden shadow-sm" x-data="{ openAiNotes: false }">
                                {{-- Header --}}
                                <button type="button" @click="openAiNotes = !openAiNotes" class="w-full bg-indigo-50 px-4 py-3 flex items-center justify-between text-indigo-800 hover:bg-indigo-100 transition-colors">
                                    <div class="flex items-center gap-2 font-bold text-sm">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        <span>Tham khảo phân tích từ AI (AI chấm: {{ $aiFeedback['overall_score'] ?? '--' }})</span>
                                    </div>
                                    <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': openAiNotes}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </button>

                                <div x-show="openAiNotes" x-transition class="p-4 bg-white space-y-4 text-sm" x-cloak>
                                    {{-- Criteria Scores --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        @foreach(['grammar', 'vocabulary', 'coherence', 'task_fulfillment'] as $criteria)
                                            @if(isset($aiFeedback['scores'][$criteria]))
                                                <div class="bg-gray-50 rounded p-2 border border-gray-100">
                                                    <div class="font-bold text-gray-700 capitalize flex justify-between">
                                                        <span>{{ str_replace('_', ' ', $criteria) }}</span>
                                                        <span class="text-indigo-600">{{ $aiFeedback['scores'][$criteria] }}/5</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Schema V3 Part Responses --}}
                                    @if($schemaVersion >= 3 && !empty($aiFeedback['part_responses']))
                                        <div class="space-y-4 mt-4 border-t border-gray-100 pt-4">
                                            @foreach($aiFeedback['part_responses'] as $idx => $response)
                                                <div class="bg-gray-50 rounded p-3 border border-gray-200">
                                                    <div class="font-bold text-gray-800 mb-2 truncate">
                                                        {{ $idx + 1 }}. {{ $response['label'] ?? 'Phần' }}
                                                    </div>
                                                    
                                                    @if(!empty($response['detailed_corrections']))
                                                        <div class="space-y-2 mb-3">
                                                            @foreach($response['detailed_corrections'] as $correction)
                                                                <div class="bg-white rounded p-2 border border-amber-100 text-xs text-gray-600">
                                                                    <div class="line-through text-gray-400">{{ $correction['original'] ?? '' }}</div>
                                                                    <div class="font-bold text-green-600">{{ $correction['corrected'] ?? '' }}</div>
                                                                    <div class="italic text-gray-500 mt-1">{{ $correction['explanation'] ?? '' }}</div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if(!empty($response['improved_sample']))
                                                        <div class="text-xs text-indigo-700 bg-indigo-50 p-2 rounded border border-indigo-100 whitespace-pre-wrap">{{ $response['improved_sample'] }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Bottom: Grading Form --}}
                <div class="p-6 bg-gray-50 border-t border-gray-200" x-data="{ score: {{ $answer->writingReview->total_score ?? 0 }} }">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Đánh giá Part {{ $answer->question->part }}</h3>

                    <div class="space-y-5">
                        {{-- Score --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Điểm (0-10)</label>
                            <div class="flex items-center gap-4">
                                <input type="range" name="scores[{{ $answer->id }}]" min="0" max="10" step="0.1"
                                    x-model="score"
                                    class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                <input type="number" step="0.1" min="0" max="10" x-model="score"
                                    class="w-20 px-2 py-1 text-center bg-white border border-gray-300 rounded-lg font-bold text-indigo-600">
                            </div>
                            <div class="flex justify-between text-xs text-gray-400 mt-1 px-1">
                                <span>0</span>
                                <span>5</span>
                                <span>10</span>
                            </div>
                        </div>

                        {{-- Comment --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nhận xét / Comment</label>
                            <textarea name="comments[{{ $answer->id }}]" rows="5"
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-y"
                                placeholder="Nhận xét về bài viết...">{{ $answer->writingReview->comment ?? '' }}</textarea>
                        </div>
                    </div>

                    @if($answer->writingReview)
                        <p class="text-xs text-gray-400 mt-3 text-center">
                            Chấm bởi {{ $answer->writingReview->reviewer->name ?? 'Admin' }}
                            · {{ $answer->writingReview->updated_at->format('d/m/Y H:i') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

        {{-- Master Submit Button --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-40">
            <div class="max-w-7xl mx-auto flex justify-end">
                <button type="submit"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Lưu toàn bộ điểm Writing
                </button>
            </div>
        </div>
    </form>

    @if(session('success'))
        <div class="fixed bottom-6 right-6 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce"
            x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ session('success') }}
        </div>
    @endif
</div>
@endsection
