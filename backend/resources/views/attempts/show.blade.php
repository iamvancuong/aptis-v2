@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $attempt->set->title }}</h1>
                    <p class="text-gray-500 mt-1">
                        {{ $attempt->set->quiz->title }} • {{ $attempt->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <div>
                    @if($attempt->attemptAnswers->where('grading_status', 'pending')->count() > 0)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                            ⏳ Đang chờ chấm
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            ✅ Đã hoàn thành
                        </span>
                    @endif
                </div>
            </div>

            {{-- Score Summary --}}
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-600 font-medium uppercase tracking-wide">Điểm số</p>
                    <p class="text-3xl font-bold text-blue-900 mt-1">
                        @if($attempt->attemptAnswers->where('grading_status', 'pending')->count() > 0)
                            --
                        @else
                            {{ $attempt->score }}
                        @endif
                    </p>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <p class="text-sm text-purple-600 font-medium uppercase tracking-wide">Thời gian</p>
                    <p class="text-3xl font-bold text-purple-900 mt-1">
                        {{ gmdate("i:s", $attempt->duration_seconds) }}
                    </p>
                </div>
                <div class="p-4 bg-teal-50 rounded-lg">
                    <p class="text-sm text-teal-600 font-medium uppercase tracking-wide">Số câu đúng</p>
                    <p class="text-3xl font-bold text-teal-900 mt-1">
                        @if($attempt->set->quiz->skill === 'writing')
                            --
                        @else
                            {{ $attempt->attemptAnswers->where('is_correct', true)->count() }} / {{ $attempt->attemptAnswers->count() }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Answers Detail --}}
        <div class="space-y-6">
            <h2 class="text-lg font-bold text-gray-900">Chi tiết bài làm</h2>
            
            @foreach($attempt->attemptAnswers as $index => $ans)
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                        <span class="font-bold text-gray-700">Câu {{ $ans->question->order }}</span>
                        @if($ans->grading_status === 'pending')
                            <span class="text-amber-600 text-sm font-medium">Đang chờ chấm</span>
                        @elseif($ans->grading_status === 'graded' && $ans->score === 0 && $ans->question->skill === 'writing')
                            <span class="text-indigo-600 text-sm font-medium">Tự đối chiếu</span>
                        @else
                            @if($ans->is_correct)
                                <span class="text-green-600 text-sm font-medium">Đúng (+{{ $ans->score }})</span>
                            @else
                                <span class="text-red-600 text-sm font-medium">Sai (0)</span>
                            @endif
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="mb-3 text-sm text-gray-800 font-medium">
                            {{ $ans->question->stem }}
                        </div>

                        {{-- User Answer --}}
                        <div class="mb-4">
                            <p class="text-xs text-gray-500 uppercase font-bold mb-1">Bài làm của bạn:</p>
                            <div class="p-3 bg-gray-50 rounded border border-gray-200 text-gray-900 whitespace-pre-wrap">
                                @if(is_array($ans->answer))
                                    {{ json_encode($ans->answer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                @else
                                    {{ $ans->answer }}
                                @endif
                            </div>
                        </div>

                        {{-- Feedback / Sample Answer --}}
                        @if($ans->grading_status === 'pending')
                            <div class="text-sm text-gray-500 italic">
                                Giáo viên sẽ chấm điểm và nhận xét bài làm của bạn sau.
                            </div>
                        @elseif($ans->feedback)
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-100 rounded">
                                <p class="text-xs text-yellow-700 uppercase font-bold mb-1">Nhận xét của giáo viên:</p>
                                <div class="text-sm text-yellow-900">{{ $ans->feedback }}</div>
                            </div>
                        @elseif($ans->question->metadata['sample_answer'] ?? false)
                            <div class="mt-4 p-4 bg-indigo-50 border border-indigo-100 rounded">
                                <p class="text-xs text-indigo-700 uppercase font-bold mb-1">Đáp án gợi ý:</p>
                                <div class="text-sm text-indigo-900 whitespace-pre-line">
                                    @if(is_array($ans->question->metadata['sample_answer']))
                                        @foreach($ans->question->metadata['sample_answer'] as $k => $v)
                                            <div><strong>{{ $k }}:</strong> {{ $v }}</div>
                                        @endforeach
                                    @else
                                        {{ $ans->question->metadata['sample_answer'] }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 flex justify-center">
            <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-white rounded-lg font-semibold shadow transition-colors">
                Quay về trang chủ
            </a>
        </div>
    </div>
</div>
@endsection
