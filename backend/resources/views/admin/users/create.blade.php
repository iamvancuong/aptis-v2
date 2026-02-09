@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:text-blue-700 mb-4 inline-block">
        ← Back to Users
    </a>
    <h1 class="text-2xl font-bold text-gray-900">Create New User</h1>
</div>

<x-card>
    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        
        <x-input 
            label="Name" 
            name="name" 
            type="text" 
            required 
            :error="$errors->first('name')"
        />

        <x-input 
            label="Email" 
            name="email" 
            type="email" 
            required 
            :error="$errors->first('email')"
        />

        <!-- Role -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Role <span class="text-red-500">*</span>
            </label>
            <select name="role" id="role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="user" {{ old('role', 'user') == 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            @error('role')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password (conditional) -->
        <div class="mb-4" id="password-field">
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input type="password" name="password" id="password"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <p class="mt-1 text-sm text-gray-500" id="password-hint"></p>
            @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Expiration Date -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date</label>
            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                min="{{ now()->addDay()->format('Y-m-d') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <p class="mt-1 text-sm text-gray-500">Leave blank for no expiration</p>
            @error('expires_at')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="blocked" {{ old('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>
            @error('status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <x-button type="submit">
                Create User
            </x-button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 inline-flex items-center">
                Cancel
            </a>
        </div>
    </form>
</x-card>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const passwordField = document.getElementById('password');
    const passwordHint = document.getElementById('password-hint');

    function updatePasswordField() {
        if (roleSelect.value === 'user') {
            passwordField.required = false;
            passwordHint.textContent = 'Default password: 12345678 (will be set automatically)';
            passwordHint.classList.remove('text-gray-500');
            passwordHint.classList.add('text-blue-600', 'font-semibold');
        } else {
            passwordField.required = true;
            passwordHint.textContent = 'Required for admin accounts (minimum 8 characters)';
            passwordHint.classList.remove('text-blue-600', 'font-semibold');
            passwordHint.classList.add('text-gray-500');
        }
    }

    roleSelect.addEventListener('change', updatePasswordField);
    updatePasswordField(); // Initial state
});
</script>
@endsection
