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

            <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200">
                {{-- Left: Question + Student Answer --}}
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
                    </div>
                </div>

                {{-- Right: Grading Form --}}
                <div class="p-6" x-data="{ score: {{ $answer->writingReview->total_score ?? 0 }} }">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Đánh giá</h3>

                    <form action="{{ route('admin.writing-reviews.grade', $answer->id) }}" method="POST" class="space-y-5">
                        @csrf

                        {{-- Score --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Điểm (0-10)</label>
                            <div class="flex items-center gap-4">
                                <input type="range" name="total_score" min="0" max="10" step="0.5"
                                    x-model="score"
                                    class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                                <span class="text-2xl font-bold text-indigo-600 w-12 text-center" x-text="score"></span>
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
                            <textarea name="comment" rows="5"
                                class="w-full px-4 py-3 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-y"
                                placeholder="Nhận xét về bài viết...">{{ $answer->writingReview->comment ?? '' }}</textarea>
                        </div>

                        {{-- Submit --}}
                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition-colors flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $answer->grading_status === 'graded' ? 'Cập nhật đánh giá' : 'Lưu đánh giá' }}
                        </button>
                    </form>

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

    @if(session('success'))
        <div class="fixed bottom-6 right-6 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce"
            x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)">
            {{ session('success') }}
        </div>
    @endif
</div>
@endsection
