@extends('layouts.admin')

@section('title', 'Chấm Điểm Speaking - ' . ($attempt->user->name ?? 'User'))

@section('content')
<div class="max-w-5xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.speaking-reviews.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 mb-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Quay lại danh sách
            </a>
            <h1 class="text-2xl font-bold text-gray-800">
                Chấm Bài Sinh Viên: <span class="text-blue-600">{{ $attempt->user->name ?? 'Unknown' }}</span>
            </h1>
            <p class="text-sm text-gray-500 mt-1">Đề thi: {{ $attempt->set->title ?? 'Không rõ' }} | Ngày nộp: {{ $attempt->finished_at ? $attempt->finished_at->format('d/m/Y H:i') : '' }}</p>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-lg mb-8 shadow-sm">
        <h3 class="text-blue-800 font-semibold mb-1 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Hướng dẫn chấm điểm
        </h3>
        <p class="text-blue-700 text-sm">Vui lòng nghe lại file thu âm của sinh viên ở từng phần. Nhập điểm và phản hồi của Giám khảo tương ứng trước khi lưu.</p>
    </div>

    <form action="{{ route('admin.speaking-reviews.grade', $attempt) }}" method="POST">
        @csrf
        
        <div class="space-y-8">
            @foreach($answers as $ans)
                @php
                    $q = $ans->question;
                    $meta = $q->metadata ?? [];
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 flex justify-between items-center">
                        <h2 class="text-lg font-bold text-gray-800">Part {{ $q->part }}</h2>
                        <span class="text-sm font-medium text-gray-500">Max Score: {{ $q->point }} PTS</span>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                        
                        <!-- Left: Prompt & Audio -->
                        <div>
                            <div class="mb-4">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-widest border-b pb-2 mb-3">Tình huống / Đề bài</h4>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <p class="text-gray-800 font-medium whitespace-pre-line">{{ $q->stem }}</p>
                                    @if(isset($meta['image_path']) || isset($meta['image_paths']))
                                        <div class="mt-3 flex gap-2 flex-wrap">
                                            @if(isset($meta['image_path']))
                                                <img src="{{ Storage::url($meta['image_path']) }}" class="h-32 rounded border border-gray-200 shadow-sm" alt="Prompt Image">
                                            @endif
                                            @if(isset($meta['image_paths']))
                                                @foreach($meta['image_paths'] as $imgPath)
                                                    <img src="{{ Storage::url($imgPath) }}" class="h-32 rounded border border-gray-200 shadow-sm" alt="Prompt Image">
                                                @endforeach
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if(isset($meta['questions']) && is_array($meta['questions']))
                                        <div class="mt-4 pt-3 border-t border-gray-200">
                                            <p class="text-sm text-gray-500 mb-2">Các câu hỏi phụ:</p>
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach($meta['questions'] as $subQ)
                                                    <li class="text-gray-800 text-sm italic">{{ $subQ }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-widest border-b pb-2 mb-3">Bản ghi âm 🎤</h4>
                                @php
                                    $audioUrls = [];
                                    if (is_array($ans->answer)) {
                                        $audioUrls = $ans->answer;
                                    } elseif (is_string($ans->answer)) {
                                        $decoded = json_decode($ans->answer, true);
                                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                            $audioUrls = $decoded;
                                        } elseif (!empty($ans->answer)) {
                                            $audioUrls = [$ans->answer];
                                        }
                                    }
                                @endphp

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
                        
                        <!-- Right: Grading Form -->
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                            <h4 class="text-sm font-semibold text-indigo-700 uppercase tracking-widest border-b border-indigo-100 pb-2 mb-4 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                Kết Quả Chấm Điểm
                            </h4>
                            
                            <div class="mb-5">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Điểm số (Tối đa: {{ $q->point }}) <span class="text-red-500">*</span></label>
                                <input type="number" step="0.1" min="0" max="{{ $q->point }}" name="grades[{{ $ans->id }}][score]" value="{{ old('grades.'.$ans->id.'.score', $ans->score) }}" class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-bold text-gray-900 transition-colors" required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nhận xét của Giám khảo (Tùy chọn)</label>
                                <textarea name="grades[{{ $ans->id }}][feedback]" rows="5" class="w-full px-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-700 transition-colors" placeholder="VD: Pronunciation is clear, but fluency needs improvement...">{{ old('grades.'.$ans->id.'.feedback', $ans->feedback) }}</textarea>
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
