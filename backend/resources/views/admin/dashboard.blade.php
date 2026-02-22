@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Xin chào đến với </h1>
    <p class="text-gray-600 mt-2">Quản lý hệ thống APTIS</p>
</div>

<div class="mb-6">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        Về Trang Luyện Tập
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <x-card>
        <div class="text-center">
            <p class="text-gray-600 text-sm">Total Quizzes</p>
            <p class="text-3xl font-bold text-blue-600">{{ \App\Models\Quiz::count() }}</p>
        </div>
    </x-card>

    <x-card>
        <div class="text-center">
            <p class="text-gray-600 text-sm">Total Users</p>
            <p class="text-3xl font-bold text-green-600">{{ \App\Models\User::count() }}</p>
        </div>
    </x-card>

    <x-card>
        <div class="text-center">
            <p class="text-gray-600 text-sm">Total Questions</p>
            <p class="text-3xl font-bold text-purple-600">{{ \App\Models\Question::count() }}</p>
        </div>
    </x-card>

    <x-card>
        <div class="text-center">
            <p class="text-gray-600 text-sm">Total Sets</p>
            <p class="text-3xl font-bold text-orange-600">{{ \App\Models\Set::count() }}</p>
        </div>
    </x-card>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-card title="Quick Actions">
        <div class="space-y-2">
            <x-button href="{{ route('admin.sets.create') }}" variant="secondary" class="w-full">
                + Create Set
            </x-button>
            <x-button href="{{ route('admin.questions.create') }}" variant="secondary" class="w-full">
                + Create Question
            </x-button>
        </div>
    </x-card>

    <x-card title="Management">
        <div class="space-y-2">
            <x-button href="{{ route('admin.sets.index') }}" variant="secondary" class="w-full">
                Manage Sets
            </x-button>
            <x-button href="{{ route('admin.questions.index') }}" variant="secondary" class="w-full">
                Manage Questions
            </x-button>
            <x-button href="{{ route('admin.users.index') }}" variant="secondary" class="w-full">
                Manage Users
            </x-button>
        </div>
    </x-card>
</div>
@endsection
