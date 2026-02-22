@extends('layouts.app')

@section('title', ucfirst($skill) . ' - APTIS Practice')

@section('content')
<div class="mb-8">
    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Quay lại Dashboard
    </a>
    <h1 class="text-3xl font-bold text-gray-900 capitalize">{{ $skill }}</h1>
    <p class="mt-2 text-gray-600">Chọn Part để luyện tập hoặc thi thử toàn bộ kỹ năng</p>
</div>

<!-- Mock Test Button -->
<div class="mb-8">
    <x-card>
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Thi thử {{ ucfirst($skill) }}</h3>
                <p class="text-gray-600 text-sm mt-1">Thi thử toàn bộ kỹ năng với timer và chấm điểm</p>
            </div>
            <x-button href="{{ route('mock-test.create', $skill) }}" variant="primary">
                Bắt đầu thi thử
            </x-button>
        </div>
    </x-card>
</div>

<!-- Parts List -->
<h2 class="text-2xl font-semibold mb-4">Danh sách Part</h2>

@if($quizzes->isEmpty())
    <x-alert type="info">
        Chưa có Part nào được công bố cho kỹ năng này.
    </x-alert>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($quizzes as $quiz)
            <x-card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Part {{ $quiz->part }}</h3>
                        <p class="text-gray-600 text-sm">{{ $quiz->title }}</p>
                        @if($quiz->duration_minutes)
                            <p class="text-gray-500 text-xs mt-1">⏱ {{ $quiz->duration_minutes }} phút</p>
                        @endif
                    </div>
                    <x-button href="{{ route('sets.index', [$skill, $quiz->part]) }}">
                        Luyện tập
                    </x-button>
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
