@extends('layouts.app')

@section('title', 'Chi tiết bài làm - ' . ucfirst($attempt->skill))

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('writingHistory.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Quay lại
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Chi tiết bài làm: {{ ucfirst($attempt->skill) }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Set: <strong class="text-gray-700">{{ $attempt->set->title ?? '—' }}</strong>
                · Hoàn thành lúc: {{ $attempt->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="text-right">
            <span class="text-4xl font-black text-indigo-600">{{ number_format($attempt->score ?? 0, 1) }}%</span>
            <p class="text-sm text-gray-500 font-medium">Tổng điểm</p>
        </div>
    </div>

    {{-- Items List --}}
    @foreach($attempt->attemptAnswers as $answer)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            {{-- Part Header --}}
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="font-bold text-gray-800 text-lg">{{ $answer->question->title ?? 'Part ' . $answer->question->part }}</h2>
                <div>
                    @if($answer->grading_status === 'graded')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 border border-green-200 shadow-sm">
                            ✅ Điểm: {{ $answer->score ?? ($answer->writingReview->total_score ?? 0) }}/10
                        </span>
                    @elseif($answer->grading_status === 'ai_graded')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                            🤖 AI Đã Chấm (Đợi duyệt)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-800">
                            ⏳ Chờ giảng viên chấm
                        </span>
                    @endif
                </div>
            </div>

            <div class="flex flex-col border-t border-gray-200">
                {{-- Top: Question + Student Answer --}}
                <div class="p-6 space-y-5">
                    {{-- Question Prompt --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Đề bài</h3>
                        <div class="bg-blue-50/50 rounded-lg p-5 text-sm text-gray-800 border border-blue-100/50">
                            <p class="font-semibold text-base mb-2">{{ $answer->question->stem }}</p>
                            @if($answer->question->metadata['scenario'] ?? false)
                                <p class="mt-2 italic text-gray-600 border-l-2 border-blue-300 pl-3">{{ $answer->question->metadata['scenario'] }}</p>
                            @endif
                            @if($answer->question->metadata['topic'] ?? false)
                                <p class="mt-3"><span class="font-semibold text-blue-900 bg-blue-100 px-2 py-0.5 rounded">Topic:</span> {{ $answer->question->metadata['topic'] }}</p>
                            @endif
                            @if($answer->question->metadata['instructions'] ?? false)
                                <p class="mt-3 text-gray-600 bg-white p-3 rounded border border-gray-100">{{ $answer->question->metadata['instructions'] }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Student's Writing --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 flex items-center gap-2">
                            Bài làm của bạn
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-5 text-sm text-gray-800 leading-relaxed border border-gray-200 min-h-[120px] shadow-inner font-medium">
                            @if(is_array($answer->answer))
                                @foreach($answer->answer as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $k => $v)
                                            <div class="mb-3 bg-white p-3 rounded border border-gray-100">
                                                <span class="font-semibold text-gray-600 block mb-1">{{ $k }}:</span>
                                                <span class="text-gray-900">{{ $v }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="mb-3 bg-white p-3 rounded border border-gray-100">
                                            @if(is_numeric($key))
                                                <p class="text-gray-900">{{ $value }}</p>
                                            @else
                                                <p><span class="font-semibold text-gray-600 block mb-1">{{ $key }}:</span> <span class="text-gray-900">{{ $value }}</span></p>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <p class="whitespace-pre-wrap">{{ $answer->answer }}</p>
                            @endif
                        </div>
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
                        <div class="mt-6 border border-emerald-200 rounded-xl overflow-hidden shadow-sm">
                            <div class="bg-emerald-50 px-5 py-3 flex items-center gap-2 font-bold text-sm text-emerald-900 border-b border-emerald-100">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Đáp án tham khảo từ Admin</span>
                            </div>
                            <div class="p-5 bg-white space-y-4">
                                @foreach($sampleAnswers as $sample)
                                    <div>
                                        <div class="text-xs font-bold text-gray-500 uppercase mb-2">{{ $sample['label'] }}</div>
                                        <div class="text-sm text-emerald-900 bg-emerald-50/50 p-4 rounded-lg font-medium leading-relaxed whitespace-pre-wrap border border-emerald-100/50">{{ trim($sample['content']) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- AI Feedback Schema V3 (Read Only for Student) --}}
                    @php
                        $hasAiFeedback = !empty($answer->ai_metadata['feedback']);
                        $isWriting = ($answer->question->skill === 'writing');
                    @endphp

                    @if($hasAiFeedback || $isWriting)
                        @php
                            $aiFeedback = $answer->ai_metadata['feedback'] ?? null;
                            $schemaVersion = $aiFeedback['schema_version'] ?? 3;
                            $gradingStatus = $answer->grading_status ?? 'pending';
                        @endphp
                        <div class="mt-6 border border-indigo-200 rounded-xl overflow-hidden shadow-sm" x-data="{ openAiNotes: true }">
                            {{-- Header --}}
                            <button type="button" @click="openAiNotes = !openAiNotes" class="w-full bg-indigo-50/80 px-5 py-3 flex items-center justify-between text-indigo-900 hover:bg-indigo-100 transition-colors border-b border-indigo-100/50">
                                <div class="flex items-center gap-2 font-bold text-sm">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                    <span>Góp ý tự động từ AI</span>
                                    @if(!$hasAiFeedback)
                                        <span class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] uppercase tracking-wider rounded-full">Đang xử lý</span>
                                    @endif
                                </div>
                                <svg class="w-4 h-4 transition-transform text-indigo-500" :class="{'rotate-180': openAiNotes}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>

                            <div x-show="openAiNotes" x-transition class="p-5 bg-white space-y-4 text-sm">
                                @if($hasAiFeedback)
                                    {{-- Criteria Summary --}}
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        @foreach(['grammar', 'vocabulary', 'coherence', 'task_fulfillment'] as $criteria)
                                            @if(isset($aiFeedback['scores'][$criteria]))
                                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 text-center">
                                                    <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">{{ str_replace('_', ' ', $criteria) }}</div>
                                                    <div class="text-lg font-black text-indigo-600">{{ $aiFeedback['scores'][$criteria] }}/5</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Schema V3 Part Responses & Corrections --}}
                                    @if($schemaVersion >= 3 && !empty($aiFeedback['part_responses']))
                                        <div class="space-y-4 mt-5">
                                            @foreach($aiFeedback['part_responses'] as $idx => $response)
                                                <div class="relative bg-white rounded-lg p-4 border border-indigo-100 shadow-sm">
                                                    <div class="absolute -top-3 left-4 bg-indigo-100 text-indigo-800 text-xs font-bold px-2 py-1 rounded-md">
                                                        {{ $response['label'] ?? 'Phần ' . ($idx + 1) }}
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        @if(!empty($response['detailed_corrections']))
                                                            <div class="space-y-3 mb-4">
                                                                <div class="text-xs font-bold text-gray-500 uppercase">Sửa lỗi:</div>
                                                                @foreach($response['detailed_corrections'] as $correction)
                                                                    <div class="bg-gray-50 rounded p-3 border border-gray-200">
                                                                        <div class="flex items-start gap-3">
                                                                            <div class="flex-1">
                                                                                <div class="line-through text-red-400 font-medium mb-1">{{ $correction['original'] ?? '' }}</div>
                                                                                <div class="text-green-600 font-bold mb-1">{{ $correction['corrected'] ?? '' }}</div>
                                                                                <div class="text-xs text-slate-500 italic bg-white p-2 border border-slate-100 rounded">{{ $correction['explanation'] ?? '' }}</div>
                                                                            </div>
                                                                        </div>
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
                                            <ul class="list-disc pl-5 text-amber-900 space-y-1 text-sm">
                                                @foreach($aiFeedback['suggestions'] as $suggestion)
                                                    <li>{{ $suggestion }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                @else
                                    {{-- Empty State / Pending --}}
                                    <div class="py-8 text-center bg-gray-50/50 rounded-xl border border-dashed border-gray-200">
                                        <div class="mb-4 relative inline-block">
                                            <div class="absolute inset-0 bg-indigo-200 blur-xl opacity-30 animate-pulse"></div>
                                            <svg class="w-12 h-12 text-indigo-300 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                        </div>
                                        <h4 class="text-base font-bold text-gray-700 mb-1">AI đang xử lý chấm điểm...</h4>
                                        <p class="text-xs text-gray-500 max-w-xs mx-auto">Vui lòng quay lại sau ít phút để xem nhận xét chi tiết và chấm điểm tự động từ AI.</p>
                                        @if(empty(config('services.openai.key')))
                                            <div class="mt-4 px-4 py-2 bg-red-50 text-red-600 rounded-lg text-xs inline-block border border-red-100">
                                                <span class="font-bold">Lưu ý:</span> AI API Key (OpenAI) chưa được cài đặt trong hệ thống.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Teacher Feedback (Bottom) --}}
                @if($answer->writingReview && $answer->grading_status === 'graded')
                    <div class="p-6 bg-emerald-50/50 border-t border-emerald-100">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold shadow-sm">
                                {{ strtoupper(substr($answer->writingReview->reviewer->name ?? 'GV', 0, 2)) }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-emerald-800 mb-1">
                                    Nhận xét của Giảng viên - {{ $answer->writingReview->reviewer->name ?? 'Giảng viên' }}
                                </h3>
                                <p class="text-xs text-emerald-600/70 mb-3">Chấm lúc: {{ $answer->writingReview->updated_at->format('H:i d/m/Y') }}</p>
                                
                                <div class="ck-content bg-white p-4 rounded-lg border border-emerald-100 text-gray-800 text-sm leading-relaxed whitespace-pre-wrap shadow-sm">{!! $answer->writingReview->comment ?: 'Giảng viên không để lại nhận xét chi tiết.' !!}</div>
                                
                                <div class="mt-4 pt-4 border-t border-emerald-100/50 flex justify-end">
                                    <div class="bg-white px-4 py-2 rounded-lg border border-emerald-200 flex items-center gap-2 shadow-sm">
                                        <span class="text-sm font-semibold text-gray-500">Điểm đánh giá Part:</span>
                                        <span class="text-xl font-black text-emerald-600">{{ $answer->writingReview->total_score }}/10</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endsection
