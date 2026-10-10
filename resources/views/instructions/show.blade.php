@extends('layouts.app')

@section('title', $instruction->title . ' - Hướng dẫn')

@section('content')
<div class="select-none" oncopy="return false" oncut="return false" oncontextmenu="return false" onselectstart="return false">
<div class="max-w-5xl mx-auto">
<x-ui.page-header :title="$instruction->title"
    :subtitle="'Cập nhật lúc ' . $instruction->updated_at->format('H:i - d/m/Y')"
    :crumbs="[['label' => 'Hướng dẫn', 'url' => route('instructions.index')], ['label' => \Illuminate\Support\Str::limit($instruction->title, 40)]]">
    @if($instruction->video_url || $instruction->video_path)
        <x-slot:aside><x-ui.chip tone="blue"><x-ui.icon name="play" class="w-4 h-4" /> Có video</x-ui.chip></x-slot:aside>
    @endif
</x-ui.page-header>
<div>
    @if($instruction->video_url)
        <div class="mb-6 rounded-2xl overflow-hidden bg-black border border-gray-200 aspect-video flex items-center justify-center relative">
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
                    <x-ui.icon name="play" class="w-12 h-12 text-gray-500 mb-4" />
                    <x-button :href="$instruction->video_url" target="_blank" rel="noopener">Mở video sang tab mới</x-button>
                </div>
            @endif
        </div>
    @elseif($instruction->video_path)
        <div class="mb-6 rounded-2xl overflow-hidden bg-black border border-gray-200 aspect-video flex items-center justify-center">
            <video controls controlsList="nodownload" class="w-full h-full object-contain">
                <source src="{{ asset('storage/' . $instruction->video_path) }}">
                Trình duyệt của bạn không hỗ trợ xem video này.
            </video>
        </div>
    @endif

    @if($instruction->content)
        <x-ui.panel class="prose max-w-none [&>div]:p-6 sm:[&>div]:p-8">
            {!! $instruction->content !!}
        </x-ui.panel>
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
</div>
@endsection
