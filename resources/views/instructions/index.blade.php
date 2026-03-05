@extends('layouts.app')

@section('title', 'Hướng dẫn')

@section('content')
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 border-b border-gray-200 pb-5">
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Tài liệu Hướng dẫn</h1>
        <p class="mt-2 text-lg text-gray-500">Video và bài viết hướng dẫn chi tiết cách sử dụng hệ thống và làm bài thi hiệu quả.</p>
    </div>

    @if($instructions->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($instructions as $instruction)
                <a href="{{ route('instructions.show', $instruction->slug) }}" class="group bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center relative overflow-hidden group-hover:bg-indigo-50 transition-colors">
                        @if($instruction->video_path || $instruction->video_url)
                            <div class="absolute inset-0 bg-black/50 flex flex-col items-center justify-center z-10 transition-opacity opacity-0 group-hover:opacity-100 backdrop-blur-sm">
                                <div class="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center shadow-lg transform scale-75 group-hover:scale-100 transition-transform duration-300">
                                    <svg class="w-6 h-6 text-indigo-600 ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                </div>
                            </div>
                            <!-- Generate a poster from video or just show an icon -->
                            <svg class="w-16 h-16 text-indigo-300 group-hover:text-indigo-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        @else
                            <svg class="w-16 h-16 text-blue-300 group-hover:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        @endif
                    </div>
                    
                    <div class="p-6 flex flex-col flex-grow">
                        <div class="flex items-center gap-2 mb-3">
                            @if($instruction->video_path || $instruction->video_url)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-xs font-semibold ring-1 ring-inset ring-indigo-600/20">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zm12.553 1.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" /></svg>
                                    Video
                                </span>
                            @endif
                            @if($instruction->content)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-semibold ring-1 ring-inset ring-blue-600/20">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Bài viết
                                </span>
                            @endif
                        </div>
                        
                        <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 mb-4">
                            {{ $instruction->title }}
                        </h3>
                        
                        <div class="mt-auto flex items-center text-sm font-medium text-indigo-600 group-hover:text-indigo-700">
                            Xem chi tiết
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        
        @if($instructions->hasPages())
            <div class="mt-10">
                {{ $instructions->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-20 bg-gray-50 rounded-2xl border border-gray-200 border-dashed">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="text-lg font-bold text-gray-700 mb-1">Chưa có hướng dẫn nào</h3>
            <p class="text-gray-500">Các video và bài viết hướng dẫn sẽ sớm được cập nhật tại đây.</p>
        </div>
    @endif
</div>
@endsection
