@extends('layouts.app')

@section('title', ucfirst($skill) . ' Part ' . $part . ' - APTIS Practice')

@section('content')
<div class="mb-8">
    <a href="{{ route('skills.show', $skill) }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Quay lại {{ ucfirst($skill) }}
    </a>
    <h1 class="text-3xl font-bold text-gray-900">{{ ucfirst($skill) }} - Part {{ $part }}</h1>
    <p class="mt-2 text-gray-600">{{ $quiz->title }}</p>
</div>

@if($sets->isEmpty())
    <x-alert type="info">
        Chưa có Bộ nào được công bố cho Part này.
    </x-alert>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($sets as $set)
            <x-card>
                <h3 class="text-lg font-semibold mb-2">{{ $set->title }}</h3>
                <p class="text-gray-600 text-sm mb-4">Bộ {{ $set->order + 1 }}</p>
                <x-button href="{{ route('practice.show', $set->id) }}" class="w-full">
                    Bắt đầu luyện tập
                </x-button>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
