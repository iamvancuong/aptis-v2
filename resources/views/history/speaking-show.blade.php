@extends('layouts.app')

@section('title', 'Chi tiết bài làm Speaking - Milaedu')

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
                    @elseif($answer->grading_status === 'ai_graded')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-800 border border-amber-200 shadow-sm">
                            🤖 AI chấm nháp: {{ number_format($answer->score ?? 0, 1) }}/10
                        </span>
                    @elseif($answer->grading_status === 'ai_failed')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            ⚠️ Chấm tự động chưa xong
                        </span>
                    @elseif($answer->grading_status === 'limit_reached')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            Đã hết lượt chấm AI
                        </span>
                    @elseif(!\App\Support\SpeakingAudio::hasRecording($answer->answer))
                        {{-- Không ghi âm thì sẽ không bao giờ có ai chấm. Hiện "Chờ chấm"
                             ở đây là hứa suông với học viên. --}}
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                            Không có bản ghi âm
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

                {{-- Nhận xét của AI (nháp tham khảo) --}}
                @php
                    $ai = $answer->ai_metadata['feedback'] ?? null;
                    $aiError = $answer->ai_metadata['error'] ?? null;
                    $aiTranscript = $answer->ai_metadata['transcript'] ?? null;
                    $criteriaLabels = [
                        'task_fulfillment' => 'Trả lời đúng yêu cầu',
                        'vocabulary' => 'Từ vựng',
                        'grammar' => 'Ngữ pháp',
                        'coherence' => 'Mạch lạc',
                    ];
                @endphp

                @if($ai)
                    <div class="p-6 bg-amber-50 border-t border-amber-100">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-amber-100 text-amber-700 rounded-full flex items-center justify-center font-bold shadow-sm">
                                AI
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-amber-800 mb-1">Nhận xét tự động (bản nháp)</h3>

                                {{-- Công bố giới hạn: cách chấm này đọc lời nói đã được
                                     chuyển thành chữ, nên không nghe được cách phát âm. --}}
                                <div class="bg-white p-3 rounded-lg border border-amber-200 text-sm text-gray-700 mb-4">
                                    <p class="font-semibold text-gray-900 mb-1">Bản nháp này đánh giá được gì?</p>
                                    <p>Máy đọc <strong>nội dung</strong> bài nói của bạn: trả lời đúng yêu cầu chưa, từ vựng, ngữ pháp, mạch lạc.</p>
                                    <p class="mt-1"><strong>Máy KHÔNG chấm phát âm và độ trôi chảy</strong> — hai mục này phải do giảng viên nghe trực tiếp mới đánh giá được. Điểm dưới đây là <strong>nháp tham khảo</strong>, không phải điểm cuối cùng.</p>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                    @foreach($criteriaLabels as $key => $label)
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">{{ $label }}</span>
                                                <span class="text-sm font-black text-amber-700">{{ $ai['scores'][$key] ?? 0 }}/5</span>
                                            </div>
                                            @if(!empty($ai['feedback'][$key]))
                                                <p class="text-sm text-gray-700 leading-relaxed">{{ $ai['feedback'][$key] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if(!empty($ai['improved_sample']))
                                    <div class="mb-4">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Gợi ý nói hay hơn</h4>
                                        <div class="bg-white p-4 rounded-lg border border-gray-200 text-sm text-gray-800 leading-relaxed whitespace-pre-wrap">{{ $ai['improved_sample'] }}</div>
                                    </div>
                                @endif

                                @if(!empty($ai['key_mistakes']))
                                    <div class="mb-4">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Lỗi cần chú ý</h4>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
                                            @foreach($ai['key_mistakes'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(!empty($ai['suggestions']))
                                    <div class="mb-4">
                                        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Gợi ý cải thiện</h4>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-gray-700">
                                            @foreach($ai['suggestions'] as $item)
                                                <li>{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($aiTranscript)
                                    {{-- Để học viên tự đối chiếu: máy nghe ra sai thì nhận xét
                                         phía trên cũng lệch theo, và họ cần biết điều đó. --}}
                                    <details class="bg-white rounded-lg border border-gray-200">
                                        <summary class="px-4 py-2 text-sm font-semibold text-gray-700 cursor-pointer">Xem máy nghe được gì từ bản ghi của bạn</summary>
                                        <div class="px-4 pb-4 pt-1 text-sm text-gray-600 leading-relaxed whitespace-pre-wrap">{{ $aiTranscript }}</div>
                                        <p class="px-4 pb-3 text-xs text-gray-500">Nếu đoạn này khác nhiều với điều bạn đã nói, nghĩa là máy nghe nhầm — hãy bỏ qua nhận xét tự động và chờ giảng viên.</p>
                                    </details>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($aiError)
                    <div class="p-6 bg-gray-50 border-t border-gray-200">
                        <p class="text-sm text-gray-700">
                            <span class="font-semibold">Chấm tự động chưa hoàn tất:</span>
                            {{ $aiError['message'] ?? 'Chấm tự động chưa hoàn tất cho phần này.' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">Lượt chấm AI của bạn không bị trừ. Bài vẫn nằm trong danh sách chờ giảng viên chấm.</p>
                    </div>
                @endif

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
