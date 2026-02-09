@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Back to Users
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
</div>

<x-card>
    <form action="{{ route('admin.users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        
        <x-input 
            label="Name" 
            name="name" 
            type="text" 
            :value="old('name', $user->name)"
            required 
            :error="$errors->first('name')"
        />

        <x-input 
            label="Email" 
            name="email" 
            type="email" 
            :value="old('email', $user->email)"
            required 
            :error="$errors->first('email')"
        />

        <!-- Password (optional) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input type="password" name="password" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Leave blank to keep current password">
            <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password (minimum 8 characters if changing)</p>
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Role -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Role <span class="text-red-500">*</span>
            </label>
            <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            @error('role')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Expiration Date -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date</label>
            <input type="date" name="expires_at" 
                value="{{ old('expires_at', $user->expires_at?->format('Y-m-d')) }}"
                min="{{ now()->addDay()->format('Y-m-d') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <p class="mt-1 text-sm text-gray-500">Leave blank for no expiration</p>
            @error('expires_at')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Status <span class="text-red-500">*</span>
            </label>
            <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="blocked" {{ old('status', $user->status) == 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>
            @error('status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <x-button type="submit">
                Update User
            </x-button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 inline-flex items-center">
                Cancel
            </a>
        </div>
    </form>
</x-card>
@endsection
