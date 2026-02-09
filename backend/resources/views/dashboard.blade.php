@extends('layouts.app')

@section('title', 'Dashboard - APTIS Practice')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Chọn kỹ năng luyện tập</h1>
    <p class="mt-2 text-gray-600">Chọn một trong ba kỹ năng để bắt đầu luyện tập</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Reading -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Reading</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng đọc hiểu</p>
            <x-button href="{{ route('skills.show', 'reading') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Listening -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Listening</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng nghe</p>
            <x-button href="{{ route('skills.show', 'listening') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>

    <!-- Writing -->
    <x-card>
        <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </div>
            <h3 class="text-xl font-semibold mb-2">Writing</h3>
            <p class="text-gray-600 text-sm mb-4">Luyện tập kỹ năng viết</p>
            <x-button href="{{ route('skills.show', 'writing') }}" class="w-full">Bắt đầu</x-button>
        </div>
    </x-card>
</div>

<div class="mt-8">
    <x-card title="Lịch sử làm bài">
        <p class="text-gray-600">Xem lịch sử các bài thi và luyện tập của bạn</p>
        <x-button href="{{ route('history.index') }}" variant="secondary" class="mt-4">Xem lịch sử</x-button>
    </x-card>
</div>
@endsection
