@extends('layouts.admin')
@section('title', 'Chỉnh sửa – ' . $grammarSet->title)
@section('header', 'Chỉnh sửa Grammar Set')

@section('content')
{{-- Top bar --}}
<div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.grammar-sets.index') }}" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="font-semibold text-gray-800">{{ $grammarSet->title }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                @if($grammarSet->status === 'published')
                    <x-badge variant="success">Published</x-badge>
                @else
                    <x-badge variant="default">Draft</x-badge>
                @endif
            </p>
        </div>
    </div>
    <span id="save-indicator" class="hidden text-xs text-green-600 font-medium"></span>
</div>

{{-- Alerts --}}
@if(session('success'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">✓ {{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        <p class="font-semibold mb-1">Không thể Publish:</p>
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form id="grammar-form" method="POST" action="{{ route('admin.grammar-sets.update', $grammarSet) }}" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="action" id="form-action" value="draft">

    {{-- Set title --}}
    <x-card>
        <x-input name="title" label="Tên bộ đề" value="{{ old('title', $grammarSet->title) }}" required error="{{ $errors->first('title') }}"/>
    </x-card>

    {{-- ── Part 1: Grammar MCQ (Q1–25) ── --}}
    <x-card title="Part 1 – Grammar (câu 1–{{ $config['mcq_count'] }})">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
            @for($i = 1; $i <= $config['mcq_count']; $i++)
                @include('admin.grammar-sets.partials._mcq', ['i' => $i])
            @endfor
        </div>
    </x-card>

    {{-- ── Part 2: Vocabulary Dropdown (Q26–30) ── --}}
    @php
        $vocabDefs = [
            1 => ['type' => 'synonym_match',       'desc' => 'Select a word from each drop-down list on the right that has the same or a very similar meaning to each word on the left.', 'connector' => '=', 'hasExample' => true],
            2 => ['type' => 'definition_match',    'desc' => 'Complete each definition using a word from the drop-down list.',                                                              'connector' => '→', 'hasExample' => false],
            3 => ['type' => 'sentence_completion', 'desc' => 'Complete each sentence using a word from each drop-down list.',                                                              'connector' => '…', 'hasExample' => false],
            4 => ['type' => 'synonym_match',       'desc' => 'Select a word from each drop-down list on the right that has the same or a very similar meaning to each word on the left.', 'connector' => '=', 'hasExample' => true],
            5 => ['type' => 'collocation_match',   'desc' => 'Select a word from each drop-down list on the right that is most often used with each word on the left.',                   'connector' => '+', 'hasExample' => true],
        ];
    @endphp
    <x-card title="Part 2 – Vocabulary (câu {{ $config['mcq_count'] + 1 }}–{{ $config['mcq_count'] + $config['vocab_count'] }})">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($vocabDefs as $vocabIdx => $vdef)
                @php $orderI = $config['mcq_count'] + $vocabIdx; @endphp
                @include('admin.grammar-sets.partials._vocab', ['vocabIdx' => $vocabIdx, 'orderI' => $orderI, 'vdef' => $vdef])
            @endforeach
        </div>
    </x-card>

    {{-- Actions --}}
    <div class="flex items-center gap-3 pb-10">
        <button type="button" id="btn-draft"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition">
            Lưu Nháp
        </button>
        <x-button type="button" id="btn-publish">Lưu</x-button>
        <a href="{{ route('admin.grammar-sets.index') }}" class="text-sm text-gray-400 hover:text-gray-600">Hủy</a>
    </div>
</form>

@include('admin.grammar-sets.partials._scripts')
@endsection
