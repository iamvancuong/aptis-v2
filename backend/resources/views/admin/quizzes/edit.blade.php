@extends('layouts.admin')

@section('title', 'Edit Quiz')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.quizzes.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Back to Quizzes
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit Quiz</h1>
</div>

<x-card>
    <form action="{{ route('admin.quizzes.update', $quiz) }}" method="POST">
        @csrf
        @method('PUT')

        <x-input 
            label="Title" 
            name="title" 
            type="text" 
            required 
            :value="old('title', $quiz->title)"
            :error="$errors->first('title')"
        />

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Skill <span class="text-red-500">*</span>
            </label>
            <select name="skill" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Skill</option>
                <option value="reading" {{ old('skill', $quiz->skill) === 'reading' ? 'selected' : '' }}>Reading</option>
                <option value="listening" {{ old('skill', $quiz->skill) === 'listening' ? 'selected' : '' }}>Listening</option>
                <option value="writing" {{ old('skill', $quiz->skill) === 'writing' ? 'selected' : '' }}>Writing</option>
            </select>
            @error('skill')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Part <span class="text-red-500">*</span>
            </label>
            <select name="part" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Select Part</option>
                <option value="1" {{ old('part', $quiz->part) == 1 ? 'selected' : '' }}>Part 1</option>
                <option value="2" {{ old('part', $quiz->part) == 2 ? 'selected' : '' }}>Part 2</option>
                <option value="3" {{ old('part', $quiz->part) == 3 ? 'selected' : '' }}>Part 3</option>
                <option value="4" {{ old('part', $quiz->part) == 4 ? 'selected' : '' }}>Part 4</option>
            </select>
            @error('part')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <x-input 
            label="Duration (minutes)" 
            name="duration_minutes" 
            type="number" 
            :value="old('duration_minutes', $quiz->duration_minutes)"
            :error="$errors->first('duration_minutes')"
        />

        <div class="mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $quiz->is_published) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Published</span>
            </label>
        </div>

        <div class="flex gap-3">
            <x-button type="submit">
                Update Quiz
            </x-button>
            <x-button href="{{ route('admin.quizzes.index') }}" variant="secondary">
                Cancel
            </x-button>
        </div>
    </form>
</x-card>
@endsection
