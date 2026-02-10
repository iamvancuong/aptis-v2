@extends('layouts.admin')

@section('title', 'Questions Management')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Questions Management</h1>
        <a href="{{ route('admin.questions.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Question
        </a>
    </div>

    <!-- Filters -->
    <x-card>
        <form method="GET" action="{{ route('admin.questions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Quiz Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quiz</label>
                <select name="quiz_id" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white">
                    <option value="">All Quizzes</option>
                    @foreach($quizzes as $quiz)
                        <option value="{{ $quiz->id }}" {{ request('quiz_id') == $quiz->id ? 'selected' : '' }}>
                            {{ $quiz->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Skill Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Skill</label>
                <select name="skill" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white">
                    <option value="">All Skills</option>
                    <option value="reading" {{ request('skill') == 'reading' ? 'selected' : '' }}>Reading</option>
                    <option value="listening" {{ request('skill') == 'listening' ? 'selected' : '' }}>Listening</option>
                    <option value="writing" {{ request('skill') == 'writing' ? 'selected' : '' }}>Writing</option>
                </select>
            </div>

            <!-- Part Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Part</label>
                <select name="part" class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white">
                    <option value="">All Parts</option>
                    <option value="1" {{ request('part') == '1' ? 'selected' : '' }}>Part 1</option>
                    <option value="2" {{ request('part') == '2' ? 'selected' : '' }}>Part 2</option>
                    <option value="3" {{ request('part') == '3' ? 'selected' : '' }}>Part 3</option>
                    <option value="4" {{ request('part') == '4' ? 'selected' : '' }}>Part 4</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                    Apply Filters
                </button>
            </div>
        </form>
    </x-card>

    <!-- Questions Table -->
    <!-- Questions Table -->
    <x-datatable 
        :data="$questions"
        :perPageOptions="[10, 20, 50]"
    >
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Set</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Skill</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Part</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>

        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($questions as $question)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $question->quiz->title }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($question->sets->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach($question->sets as $set)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $set->title }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="text-gray-400 italic">No Set</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $question->skill === 'reading' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $question->skill === 'listening' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $question->skill === 'writing' ? 'bg-green-100 text-green-800' : '' }}
                        ">
                            {{ ucfirst($question->skill) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                        Part {{ $question->part }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ str_replace('_', ' ', ucfirst($question->type)) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                        {{ $question->point }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <!-- Edit Button -->
                        <a 
                            href="{{ route('admin.questions.edit', $question) }}" 
                            class="inline-flex items-center px-2 py-1 bg-violet-500 text-white text-xs rounded hover:bg-violet-600 transition"
                        >
                            Edit
                        </a>

                        <!-- Delete Button -->
                        <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                onclick="return confirm('Are you sure you want to delete this question?')"
                                class="inline-flex items-center px-2 py-1 bg-pink-500 text-white text-xs rounded hover:bg-pink-600 transition"
                            >
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        No questions found. <a href="{{ route('admin.questions.create') }}" class="text-indigo-600 hover:underline">Create your first question</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </x-datatable>
</div>
@endsection
