@extends('layouts.admin')

@section('title', 'Create Set')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.sets.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Back to Sets
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Create New Set</h1>
</div>

<x-card>
    <form action="{{ route('admin.sets.store') }}" method="POST">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Quiz <span class="text-red-500">*</span>
            </label>
            <select name="quiz_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Quiz</option>
                @foreach($quizzes as $quiz)
                    <option value="{{ $quiz->id }}" {{ old('quiz_id') == $quiz->id ? 'selected' : '' }}>
                        {{ $quiz->title }}
                    </option>
                @endforeach
            </select>
            @error('quiz_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <x-input 
            label="Title" 
            name="title" 
            type="text" 
            required 
            :error="$errors->first('title')"
        />

        <x-input 
            label="Order (0-indexed)" 
            name="order" 
            type="number" 
            value="0"
            :error="$errors->first('order')"
        />

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Public</span>
            </label>
        </div>

        <div class="flex gap-3">
            <x-button type="submit">
                Create Set
            </x-button>
            <x-button href="{{ route('admin.sets.index') }}" variant="secondary">
                Cancel
            </x-button>
        </div>
    </form>
</x-card>
@endsection
