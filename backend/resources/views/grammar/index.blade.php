@extends('layouts.app')
@section('title', 'Grammar & Vocabulary – APTIS Practice')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
            <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Dashboard
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Grammar & Vocabulary</h1>
        <p class="mt-2 text-gray-500">
            30 câu (25 Grammar MCQ + 5 Vocabulary Dropdown) · 25 phút
        </p>
    </div>

    @if($sets->isEmpty())
        <div class="text-center py-16 bg-white rounded-2xl border border-gray-100">
            <svg class="w-14 h-14 mx-auto text-gray-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-gray-400">Chưa có bộ đề nào được công bố.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($sets as $set)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md hover:border-indigo-100 transition-all overflow-hidden">
                <div class="p-5">
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <h3 class="font-semibold text-gray-900 text-lg leading-tight">{{ $set->title }}</h3>
                        <span class="shrink-0 text-xs font-medium bg-indigo-50 text-indigo-600 px-2.5 py-1 rounded-full">
                            {{ $set->questions_count }} câu
                        </span>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500 mb-4">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            25 phút
                        </span>
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            MCQ + Vocabulary
                        </span>
                    </div>

                    <a href="{{ route('practice.show', $set->id) }}"
                       class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-2.5 rounded-xl transition">
                        Bắt đầu luyện tập →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
