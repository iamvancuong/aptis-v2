@extends('layouts.app')

@section('title', $set->title . ' - APTIS Practice')

@section('content')
<div class="mb-6">
    <a href="{{ route('sets.index', [$set->quiz->skill, $set->quiz->part]) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
        ← Quay lại danh sách Sets
    </a>
</div>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $set->title }}</h1>
            <p class="mt-2 text-gray-600">
                {{ $set->quiz->name }} - Part {{ $set->quiz->part }} 
                <span class="mx-2">•</span>
                <span class="capitalize">{{ $set->quiz->skill }}</span>
            </p>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold text-indigo-600">{{ $set->questions->count() }}</div>
            <div class="text-sm text-gray-500">câu hỏi</div>
        </div>
    </div>
</div>

<!-- Questions List -->
<div class="space-y-4">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Danh sách câu hỏi</h2>
    
    @if($set->questions->count() > 0)
        @foreach($set->questions as $index => $question)
            <x-card>
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <!-- Question Header -->
                        <div class="flex items-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full font-semibold text-sm mr-3">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1">
                                @if($question->stem)
                                    <p class="text-gray-900 font-medium">{{ $question->stem }}</p>
                                @else
                                    <p class="text-gray-900 font-medium">{{ $question->type }}</p>
                                @endif
                                <p class="text-xs text-gray-500 mt-1">{{ $question->point }} điểm</p>
                            </div>
                        </div>

                        <!-- Audio Indicator -->
                        @if($question->audio_path)
                            <div class="mt-2 ml-11">
                                <div class="flex items-center text-sm text-green-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    </svg>
                                    <span>Có audio</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </x-card>
        @endforeach
    @else
        <x-card>
            <p class="text-gray-500 text-center py-8">Chưa có câu hỏi nào trong set này.</p>
        </x-card>
    @endif
</div>

<!-- Practice Button -->
<div class="mt-8 flex justify-center">
    <x-button href="{{ route('practice.show', $set->id) }}" size="lg">
        Bắt đầu luyện tập
    </x-button>
</div>
@endsection
