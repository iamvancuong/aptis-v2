@extends('layouts.app')

@section('title', ucfirst($skill) . ' Part ' . $part . ' - APTIS Practice')

@section('content')
<div class="mb-8">
    <a href="{{ route('skills.show', $skill) }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Quay lại {{ ucfirst($skill) }}
    </a>
    <h1 class="text-3xl font-bold text-gray-900">{{ ucfirst($skill) }} - Part {{ $part }}</h1>
    <p class="mt-2 text-gray-600">{{ $quiz->title }}</p>
    @if($quiz->duration_minutes)
        <p class="text-sm text-gray-500 mt-1">⏱ Thời gian: {{ $quiz->duration_minutes }} phút</p>
    @endif
</div>

@if($sets->isEmpty())
    <x-alert type="info">
        Chưa có Bộ nào được công bố cho Part này.
    </x-alert>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($sets as $set)
            <x-card>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $set->title }}</h3>
                    <p class="text-sm text-gray-500">Bộ {{ $set->order + 1 }}</p>
                </div>

                <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Số câu hỏi:</span>
                        <span class="text-lg font-bold text-indigo-600">{{ $set->questions_count }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <x-button href="{{ route('sets.show', $set) }}" variant="secondary" class="w-full">
                        Xem chi tiết
                    </x-button>
                    <x-button href="{{ route('practice.show', $set->id) }}" class="w-full">
                        Bắt đầu luyện tập
                    </x-button>
                </div>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
