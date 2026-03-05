@extends('layouts.app')

@section('title', 'Chi tiết bài làm Speaking - APTIS Practice')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('speakingHistory.index') }}" class="text-sm text-rose-600 hover:text-rose-800 flex items-center gap-1 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                Quay lại
            </a>
            <h1 class="text-2xl font-bold text-gray-900">Chi tiết bài làm: Speaking 🎤</h1>
            <p class="text-sm text-gray-500 mt-1">
                Set: <strong class="text-gray-700">{{ $attempt->set->title ?? '—' }}</strong>
                · Hoàn thành lúc: {{ $attempt->created_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="text-right">
            <span class="text-4xl font-black text-rose-600">{{ number_format($attempt->score ?? 0, 1) }}%</span>
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
                            ✅ Điểm: {{ number_format($answer->score ?? 0, 1) }}/10
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
                <div class="p-6 space-y-6">
                    {{-- Question Prompt --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Đề bài</h3>
                        <div class="bg-rose-50/50 rounded-lg p-5 text-sm text-gray-800 border border-rose-100/50">
                            <p class="font-semibold text-base mb-3 leading-relaxed">{{ $answer->question->stem }}</p>
                            
                            @if($answer->question->part === 2 || $answer->question->part === 4)
                                @if(!empty($answer->question->metadata['image_path']))
                                    <div class="mb-4 text-center bg-white p-2 rounded-lg border border-gray-100">
                                        <img src="{{ asset('storage/' . $answer->question->metadata['image_path']) }}" alt="Speaking Image" class="max-h-64 object-contain mx-auto rounded-lg shadow-sm">
                                    </div>
                                @endif
                            @endif

                            @if($answer->question->part === 3)
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    @foreach($answer->question->metadata['image_paths'] ?? [] as $path)
                                        <div class="bg-white p-2 rounded-lg border border-gray-100 flex items-center justify-center">
                                            <img src="{{ asset('storage/' . $path) }}" alt="Speaking Image" class="h-48 md:h-56 object-contain mx-auto rounded-lg shadow-sm">
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="space-y-2">
                                @foreach($answer->question->metadata['questions'] ?? [] as $idx => $q)
                                    <div class="flex gap-2 items-start text-gray-700">
                                        <span class="font-bold text-rose-600">Q{{ $idx + 1 }}:</span>
                                        <span class="italic font-medium">{{ $q }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Student's Recording --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                             🎤 Bài làm (Bản ghi âm của bạn)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @php
                                $audioFiles = $answer->answer;
                                if (!is_array($audioFiles)) {
                                    $audioFiles = json_decode($audioFiles, true) ?? [];
                                }
                                // Ensure we have a flat array of strings
                                $flatAudioFiles = [];
                                array_walk_recursive($audioFiles, function($a) use (&$flatAudioFiles) {
                                    if (is_string($a) && !empty($a)) {
                                        $flatAudioFiles[] = $a;
                                    }
                                });
                            @endphp

                            @forelse($flatAudioFiles as $idx => $filePath)
                                <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm flex flex-col gap-2">
                                    <span class="text-xs font-bold text-gray-400 uppercase tracking-tight">Bản ghi #{{ $idx + 1 }}</span>
                                    <audio controls class="w-full h-10">
                                        <source src="{{ asset('storage/' . $filePath) }}" type="audio/webm">
                                        Browser không hỗ trợ audio.
                                    </audio>
                                </div>
                            @empty
                                <div class="col-span-full py-4 text-center bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                    <p class="text-sm text-gray-500 italic">Không tìm thấy bản ghi âm cho phần này.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Sample Answer (Reference Answer) --}}
                    @if(!empty($answer->question->metadata['sample_answer']))
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <h3 class="text-xs font-bold text-rose-500 uppercase tracking-wider mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Đáp án tham khảo từ Admin
                            </h3>
                            <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 text-gray-700 text-sm leading-relaxed whitespace-pre-wrap italic">{{ trim($answer->question->metadata['sample_answer']) }}</div>
                        </div>
                    @endif
                </div>

                {{-- Teacher Feedback (Bottom) --}}
                @if($answer->grading_status === 'graded' && $answer->feedback)
                    <div class="p-6 bg-emerald-50/50 border-t border-emerald-100">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center font-bold shadow-sm">
                                GV
                            </div>
                            <div class="flex-1">
                                <h3 class="text-sm font-bold text-emerald-800 mb-1">
                                    Nhận xét của Giảng viên
                                </h3>
                                
                                <div class="bg-white p-4 rounded-lg border border-emerald-100 text-gray-800 text-sm leading-relaxed whitespace-pre-wrap shadow-sm">{{ $answer->feedback ?: 'Giảng viên không để lại nhận xét chi tiết.' }}</div>
                                
                                <div class="mt-4 pt-4 border-t border-emerald-100/50 flex justify-end">
                                    <div class="bg-white px-4 py-2 rounded-lg border border-emerald-200 flex items-center gap-2 shadow-sm">
                                        <span class="text-sm font-semibold text-gray-500">Điểm đánh giá Part:</span>
                                        <span class="text-xl font-black text-emerald-600">{{ number_format($answer->score ?? 0, 1) }}/10</span>
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
