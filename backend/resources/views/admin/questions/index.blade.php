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
                <select name="quiz_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
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
                <select name="skill" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All Skills</option>
                    <option value="reading" {{ request('skill') == 'reading' ? 'selected' : '' }}>Reading</option>
                    <option value="listening" {{ request('skill') == 'listening' ? 'selected' : '' }}>Listening</option>
                    <option value="writing" {{ request('skill') == 'writing' ? 'selected' : '' }}>Writing</option>
                </select>
            </div>

            <!-- Part Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Part</label>
                <select name="part" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
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
    <x-card>
        <x-datatable 
            :data="$questions"
            :perPageOptions="[10, 20, 50]"
        >
            <x-slot name="header">
                <x-datatable.header>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quiz</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Skill</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Part</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Preview</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Points</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </x-datatable.header>
            </x-slot>

            @forelse($questions as $question)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ $question->quiz->name }}
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
                    <td class="px-6 py-4 text-sm text-gray-600">
                        @if($question->type === 'fill_in_blanks_mc')
                            {{ Str::limit($question->metadata['paragraphs'][0] ?? 'N/A', 50) }}
                        @elseif($question->type === 'sentence_ordering')
                            {{ Str::limit($question->metadata['sentences'][0] ?? 'N/A', 50) }}
                        @elseif($question->type === 'text_question_match')
                            {{ count($question->metadata['items'] ?? []) }} texts, {{ count($question->metadata['options'] ?? []) }} questions
                        @elseif($question->type === 'paragraph_heading_match')
                            {{ count($question->metadata['paragraphs'] ?? []) }} paragraphs
                        @else
                            {{ Str::limit($question->stem ?? 'N/A', 50) }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                        {{ $question->point }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <!-- Preview Button -->
                        <button 
                            onclick="openPreview({{ $question->id }})"
                            class="inline-flex items-center px-2 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600 transition"
                            title="Preview"
                        >
                            👁️
                        </button>

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
        </x-datatable>
    </x-card>
</div>

<!-- Preview Modal (Placeholder for now) -->
<div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold">Question Preview</h2>
            <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div id="previewContent">
            Loading...
        </div>
    </div>
</div>

<script>
function openPreview(questionId) {
    // TODO: Implement preview via AJAX
    document.getElementById('previewModal').classList.remove('hidden');
    document.getElementById('previewContent').innerHTML = 'Preview for question ' + questionId + ' (Coming soon)';
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
}
</script>
@endsection
