@extends('layouts.admin')

@section('title', 'Chấm Điểm Speaking - ' . ($attempt->user->name ?? 'User'))

@section('content')
<div class="space-y-6 pb-24 max-w-7xl mx-auto">

    {{-- Top Header Area --}}
    <div>
        <a href="{{ route('admin.speaking-reviews.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-indigo-600 transition-colors mb-4">
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
                        Nộp lúc: <span class="text-gray-900">{{ $attempt->finished_at ? $attempt->finished_at->format('H:i - d/m/Y') : '—' }}</span>
                    </div>
                </div>
            </div>
            
            {{-- Progress Badge --}}
            <div class="shrink-0">
                @php
                    $allGraded = $answers->every(fn($a) => $a->grading_status === 'graded');
                    $gradedCount = $answers->where('grading_status', 'graded')->count();
                    $totalCount = $answers->count();
                @endphp
                @if($allGraded && $totalCount > 0)
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

    <form action="{{ route('admin.speaking-reviews.grade', $attempt) }}" method="POST">
        @csrf
        
        <div class="space-y-8">
            @foreach($answers as $ans)
                @php
                    $q = $ans->question;
                    $meta = $q->metadata ?? [];
                    
                    // Robust flattening of audio file paths
                    $audioUrls = [];
                    $rawAnswer = $ans->answer;
                    if (is_string($rawAnswer)) {
                        $rawAnswer = json_decode($rawAnswer, true) ?: ($rawAnswer === 'recorded' ? [] : [$rawAnswer]);
                    }
                    
                    if (is_array($rawAnswer)) {
                        array_walk_recursive($rawAnswer, function($a) use (&$audioUrls) {
                            if (is_string($a) && !empty($a) && $a !== 'recorded') {
                                $audioUrls[] = $a;
                            }
                        });
                    }
                @endphp
                
                @if(count($audioUrls) === 0 && ($q->part === 1))
                     @continue
                @endif

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    {{-- Header of the Part --}}
                    <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap items-center justify-between gap-4 bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white font-bold text-lg flex items-center justify-center shadow-sm">
                                {{ $q->part }}
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">{{ $q->title ?? 'Speaking Part ' . $q->part }}</h2>
                                <p class="text-sm text-gray-500">Max Score: 10 PTS</p>
                            </div>
                        </div>
                        <div>
                            @if($ans->grading_status === 'graded')
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-green-100/50 border border-green-200 rounded-lg">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    <span class="text-sm font-semibold text-green-700">Điểm: {{ $ans->score ?? 0 }}/10</span>
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
                                    <p class="text-gray-800 font-medium whitespace-pre-line">{{ $q->stem }}</p>
                                    @if(isset($meta['image_path']) || isset($meta['image_paths']))
                                        <div class="mt-3 flex gap-2 flex-wrap">
                                            @if(isset($meta['image_path']))
                                                <img src="{{ Storage::url($meta['image_path']) }}" class="h-48 rounded border border-gray-200 shadow-sm object-contain" alt="Prompt Image">
                                            @endif
                                            @if(isset($meta['image_paths']))
                                                @foreach($meta['image_paths'] as $imgPath)
                                                    <img src="{{ Storage::url($imgPath) }}" class="h-48 rounded border border-gray-200 shadow-sm object-contain" alt="Prompt Image">
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if(isset($meta['questions']) && is_array($meta['questions']))
                                        <div class="mt-4 pt-3 border-t border-gray-200">
                                            <p class="text-sm text-gray-500 mb-2 font-bold uppercase tracking-tight">Các câu hỏi phụ:</p>
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach($meta['questions'] as $subQ)
                                                    <li class="text-gray-800 text-sm italic">{{ $subQ }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if(!empty($meta['sample_answer']))
                                        <div class="mt-4 pt-4 border-t border-emerald-100" x-data="{ open: false }">
                                            <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-left focus:outline-none group">
                                                <span class="text-xs text-emerald-700 font-bold uppercase tracking-wider flex items-center gap-2">
                                                    <span class="p-1 rounded bg-emerald-100/50 group-hover:bg-emerald-200/50 transition-colors">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    </span>
                                                    Đáp án tham khảo (Admin)
                                                </span>
                                                <svg class="w-4 h-4 text-emerald-500 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <div x-show="open" x-collapse x-cloak class="mt-3 bg-emerald-50/50 border border-emerald-100 p-4 rounded-xl">
                                                <div class="text-sm text-emerald-900 leading-relaxed whitespace-pre-wrap font-medium">{{ trim($meta['sample_answer']) }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- AI Feedback --}}
                            @php
                                $hasAiFeedback = !empty($ans->ai_metadata['feedback']);
                                $aiFeedback = $ans->ai_metadata['feedback'] ?? null;
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
                                                                        <div class="text-xs font-bold text-gray-500 uppercase mb-2 mt-4 pt-4 border-t border-gray-100">Bài mẫu tham khảo (*Speech):</div>
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
                            @endif
                            
                            <div>
                                <h3 class="font-bold text-gray-900 text-sm tracking-wide mb-3 mt-6 border-t pt-4 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                    BẢN GHI ÂM CỦA HỌC SINH
                                </h3>
                                {{-- Already handled in loop top --}}

                                @if(count($audioUrls) > 0)
                                    <div class="space-y-3">
                                        @foreach($audioUrls as $idx => $audioPath)
                                            <div class="bg-gray-100 p-3 rounded-lg flex items-center gap-3">
                                                <span class="text-sm font-bold text-gray-500 w-6">#{{ count($audioUrls) > 1 ? $idx+1 : 1 }}</span>
                                                <audio controls class="w-full">
                                                    <source src="{{ Storage::url($audioPath) }}" type="audio/webm">
                                                    Your browser doesn't support audio.
                                                </audio>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-4 bg-red-50 text-red-600 rounded-lg text-sm flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        Học viên không nộp bản ghi âm hoặc có lỗi trong quá trình lưu.
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- ================= RIGHT COLUMN ================= --}}
                        <div class="p-6 flex flex-col gap-6 bg-white">
                            
                            {{-- Form Container --}}
                            <div class="bg-indigo-50/30 rounded-xl border border-indigo-100 p-5 shrink-0"
                                 x-data="{ score: {{ $ans->score ?? 0 }} }">
                                 
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
                                        <input type="range" name="grades[{{ $ans->id }}][score]" min="0" max="10" step="0.5"
                                            x-model="score"
                                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                                    </div>

                                    {{-- Quick Preset Buttons --}}
                                    <div>
                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                $presets = [0, 2, 4, 5, 6, 7, 8, 9, 10];
                                            @endphp
                                            
                                            @foreach($presets as $preset)
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
                                        <textarea name="grades[{{ $ans->id }}][feedback]" rows="3" id="comment-{{ $ans->id }}"
                                            class="ckeditor w-full px-3 py-2 text-sm text-gray-800 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-400 resize-y bg-white"
                                            placeholder="Ghi chú lỗi sai, khen ngợi...">{{ old('grades.'.$ans->id.'.feedback', $ans->feedback) }}</textarea>
                                    </div>
                                    
                                    @if($ans->grading_status === 'graded')
                                        <div class="pt-2">
                                            <p class="text-xs text-gray-500 text-right">
                                                Cập nhật lần cuối: <strong class="text-gray-700">{{ $ans->updated_at->format('d/m/Y H:i') }}</strong>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Sticky Action Bar -->
        <div class="sticky bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.05)] mt-8 p-4 flex justify-end gap-3 rounded-t-xl">
            <a href="{{ route('admin.speaking-reviews.index') }}" class="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                Hủy
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 shadow-md transition-colors flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                Ghi Nhận Điểm
            </button>
        </div>
    </form>
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
