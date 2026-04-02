@extends('layouts.app')

@section('title', $instruction->title . ' - Hướng dẫn')

@section('content')
<div class="select-none" oncopy="return false" oncut="return false" oncontextmenu="return false" onselectstart="return false">
<div class="w-full bg-gradient-to-br from-indigo-50 via-white to-blue-50 border-b border-gray-200 shadow-sm relative overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 right-0 -translate-y-12 translate-x-1/3 opacity-20 pointer-events-none">
        <svg fill="currentColor" viewBox="0 0 24 24" class="w-96 h-96 text-indigo-300 transform -rotate-12"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 22C6.486 22 2 17.514 2 12S6.486 2 12 2s10 4.486 10 10-4.486 10-10 10z"></path></svg>
    </div>

    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 relative z-10">
        <a href="{{ route('instructions.index') }}" class="inline-flex items-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-all mb-6 group bg-white/60 hover:bg-white pl-2.5 pr-4 py-1.5 rounded-full shadow-sm ring-1 ring-indigo-600/10 backdrop-blur-sm">
            <svg class="w-4 h-4 mr-1.5 transform group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Quay lại danh mục
        </a>
        
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight leading-loose max-w-4xl" style="line-height: 1.3;">
            {{ $instruction->title }}
        </h1>
        
        <div class="flex items-center gap-5 mt-8 text-sm font-medium text-gray-500">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Cập nhật lúc: <span class="text-gray-700">{{ $instruction->updated_at->format('H:i - d/m/Y') }}</span></span>
            </div>
            
            @if($instruction->video_url || $instruction->video_path)
                <div class="h-4 w-px bg-gray-300"></div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-100 text-indigo-700">
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" /></svg>
                    Bao gồm Video
                </div>
            @endif
        </div>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @if($instruction->video_url)
        <div class="mb-12 rounded-2xl overflow-hidden bg-black shadow-2xl border border-gray-200 aspect-video flex items-center justify-center relative">
            @if(Str::contains($instruction->video_url, 'youtube.com') || Str::contains($instruction->video_url, 'youtu.be'))
                @php
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $instruction->video_url, $matches);
                    $youtubeId = $matches[1] ?? '';
                @endphp
                @if($youtubeId)
                    <iframe class="absolute inset-0 w-full h-full" src="https://www.youtube.com/embed/{{ $youtubeId }}?rel=0" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                @else
                    <div class="text-white text-center p-6 bg-red-900/50 rounded-lg">Link YouTube không hợp lệ</div>
                @endif
            @elseif(Str::contains($instruction->video_url, 'drive.google.com'))
                @php
                    $driveUrl = preg_replace('/\/view.*/', '/preview', $instruction->video_url);
                @endphp
                <iframe class="absolute inset-0 w-full h-full" src="{{ $driveUrl }}" frameborder="0" allow="autoplay" allowfullscreen></iframe>
            @else
                <div class="text-white flex flex-col items-center justify-center p-8 bg-gray-900 w-full h-full">
                    <svg class="w-16 h-16 text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    <a href="{{ $instruction->video_url }}" target="_blank" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 transition-colors text-white font-semibold rounded-lg shadow-sm">
                        Mở Video sang Tab mới
                    </a>
                </div>
            @endif
        </div>
    @elseif($instruction->video_path)
        <div class="mb-12 rounded-2xl overflow-hidden bg-black shadow-2xl border border-gray-200 aspect-video flex items-center justify-center">
            <video controls controlsList="nodownload" class="w-full h-full object-contain">
                <source src="{{ asset('storage/' . $instruction->video_path) }}">
                Trình duyệt của bạn không hỗ trợ xem video này.
            </video>
        </div>
    @endif

    @if($instruction->content)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 sm:p-10 prose prose-indigo max-w-none">
            {!! $instruction->content !!}
        </div>
    @endif
</div>

<script>
    document.addEventListener('keydown', function(e) {
        // Chặn F12, Ctrl+C, Ctrl+X, Ctrl+U, Ctrl+S, Ctrl+P
        if (e.ctrlKey && (e.key === 'c' || e.key === 'C' || e.key === 'x' || e.key === 'X' || e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P')) {
            e.preventDefault();
        }
        if (e.key === 'F12') {
            e.preventDefault();
        }
    });
</script>
</div>
@endsection
