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

        {{-- Màn này chỉ tạo học viên (role user); role được ép ở server.
             Không có ô mật khẩu: mọi tài khoản tạo tay dùng mặc định 12345678. --}}
        <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 px-3 py-2">
            <p class="text-sm text-blue-700">
                Mật khẩu mặc định: <span class="font-semibold">12345678</span> — học viên đổi ở lần đăng nhập đầu.
            </p>
        </div>

        <!-- Expiration Date -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Ngày thi (Exam Date)</label>
            <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}"
                min="{{ now()->format('Y-m-d') }}"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <p class="mt-1 text-sm text-gray-500">Để trống nếu không giới hạn. Tài khoản sẽ hết hạn vào 23:59 của ngày này.</p>
            @error('expires_at')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="blocked" {{ old('status') == 'blocked' ? 'selected' : '' }}>Blocked</option>
            </select>
            @error('status')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Target Level -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Target Level (Mục tiêu)</label>
            <select name="target_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Không bắt buộc (Mặc định B2)</option>
                <option value="A1" {{ old('target_level') == 'A1' ? 'selected' : '' }}>A1</option>
                <option value="A2" {{ old('target_level') == 'A2' ? 'selected' : '' }}>A2</option>
                <option value="B1" {{ old('target_level') == 'B1' ? 'selected' : '' }}>B1</option>
                <option value="B2" {{ old('target_level') == 'B2' ? 'selected' : '' }}>B2</option>
                <option value="C1" {{ old('target_level') == 'C1' ? 'selected' : '' }}>C1</option>
                <option value="C2" {{ old('target_level') == 'C2' ? 'selected' : '' }}>C2</option>
            </select>
            @error('target_level')
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
@endsection
