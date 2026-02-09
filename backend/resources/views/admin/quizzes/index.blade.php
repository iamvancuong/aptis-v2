@extends('layouts.admin')

@section('title', 'Quiz Management')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">Quiz Management</h1>
    <x-button href="{{ route('admin.quizzes.create') }}">
        + Create Quiz
    </x-button>
</div>

{{-- @if(session('success'))
    <x-alert type="success" class="mb-4">
        {{ session('success') }}
    </x-alert>
@endif --}}

<!-- Quizzes Datatable -->
<x-datatable :data="$quizzes" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Skill</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Part</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($quizzes as $quiz)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ ($quizzes->currentPage() - 1) * $quizzes->perPage() + $loop->iteration }}</td>
                <td class="px-6 py-4 text-sm">{{ $quiz->title }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$quiz->skill === 'reading' ? 'default' : ($quiz->skill === 'listening' ? 'success' : 'warning')">
                        {{ ucfirst($quiz->skill) }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">Part {{ $quiz->part }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $quiz->duration_minutes }} min</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$quiz->is_published ? 'success' : 'danger'">
                        {{ $quiz->is_published ? 'Published' : 'Draft' }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="text-blue-600 hover:text-blue-700 mr-3">
                        Edit
                    </a>
                    <form action="{{ route('admin.quizzes.destroy', $quiz) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700">
                            Delete
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                    No quizzes found. <a href="{{ route('admin.quizzes.create') }}" class="text-blue-600">Create one</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
@endsection
