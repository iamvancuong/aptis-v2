@extends('layouts.app')

@section('title', 'Hướng dẫn - Milaedu')

@section('content')
<x-ui.page-header title="Tài liệu hướng dẫn"
    subtitle="Video và bài viết hướng dẫn cách dùng hệ thống và làm bài thi hiệu quả"
    :crumbs="[['label' => 'Luyện tập', 'url' => route('dashboard')], ['label' => 'Hướng dẫn']]" />

@if($instructions->isEmpty())
    <x-ui.empty-state title="Chưa có hướng dẫn nào" icon="book">
        Các video và bài viết hướng dẫn sẽ sớm được cập nhật tại đây.
    </x-ui.empty-state>
@else
    <x-ui.section-title title="Tất cả hướng dẫn" :meta="$instructions->total() . ' bài'" />
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
        @foreach($instructions as $instruction)
            @php $coVideo = $instruction->video_path || $instruction->video_url; @endphp
            <x-ui.tile :href="route('instructions.show', $instruction->slug)" class="!p-0 overflow-hidden">
                <span class="aspect-video bg-gray-50 border-b border-gray-100 flex items-center justify-center">
                    <x-ui.icon-badge :icon="$coVideo ? 'play' : 'document'" tone="bg-white text-blue-600 border border-gray-200" size="lg" />
                </span>
                <span class="flex flex-col flex-1 p-5">
                    <span class="flex gap-1.5">
                        @if($coVideo)<span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">Video</span>@endif
                        @if($instruction->content)<span class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-600 text-xs font-medium">Bài viết</span>@endif
                    </span>
                    <span class="block mt-3 text-base font-semibold text-gray-900 line-clamp-2 group-hover:text-blue-700 transition-colors">{{ $instruction->title }}</span>
                    <span class="block mt-auto pt-4 text-sm font-medium text-blue-600">Xem chi tiết →</span>
                </span>
            </x-ui.tile>
        @endforeach
    </div>

    @if($instructions->hasPages())
        <div class="mt-6">{{ $instructions->links() }}</div>
    @endif
@endif
@endsection
