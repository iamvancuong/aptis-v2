@extends('layouts.admin')

@section('title', 'Set Management')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">Set Management</h1>
    <x-button href="{{ route('admin.sets.create') }}">
        + Create Set
    </x-button>
</div>

{{-- @if(session('success'))
    <x-alert type="success" class="mb-4">
        {{ session('success') }}
    </x-alert>
@endif --}}

<!-- Sets Datatable -->
<x-datatable :data="$sets" :per-page-options="[10, 20, 50]">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quiz</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($sets as $set)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ ($sets->currentPage() - 1) * $sets->perPage() + $loop->iteration }}</td>
                <td class="px-6 py-4 text-sm">{{ $set->title }}</td>
                <td class="px-6 py-4 text-sm">
                    <div class="flex items-center gap-2">
                        <x-badge :variant="$set->quiz->skill === 'reading' ? 'default' : ($set->quiz->skill === 'listening' ? 'success' : 'warning')">
                            {{ ucfirst($set->quiz->skill) }}
                        </x-badge>
                        <span class="text-xs text-gray-600">Part {{ $set->quiz->part }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $set->quiz->title }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $set->order + 1 }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <x-badge :variant="$set->is_public ? 'success' : 'danger'">
                        {{ $set->is_public ? 'Public' : 'Private' }}
                    </x-badge>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                    <a href="{{ route('admin.sets.edit', $set) }}" class="text-blue-600 hover:text-blue-700 mr-3">
                        Edit
                    </a>
                    <form action="{{ route('admin.sets.destroy', $set) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
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
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                    No sets found. <a href="{{ route('admin.sets.create') }}" class="text-blue-600">Create one</a>
                </td>
            </tr>
        @endforelse
    </tbody>
</x-datatable>
@endsection
