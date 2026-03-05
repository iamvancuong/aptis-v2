@extends('layouts.guest')

@section('title', 'Đăng ký - APTIS Practice')

@section('content')
<x-card>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Đăng ký tài khoản</h2>
        <p class="text-gray-600 mt-1">Tạo tài khoản để bắt đầu luyện tập</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <x-input 
            label="Họ tên" 
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

        <x-input 
            label="Mật khẩu" 
            name="password" 
            type="password" 
            required 
            :error="$errors->first('password')"
        />

        <x-input 
            label="Xác nhận mật khẩu" 
            name="password_confirmation" 
            type="password" 
            required 
        />

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Mục tiêu điểm số (Tùy chọn)</label>
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

        <x-button type="submit" class="w-full mb-4">
            Đăng ký
        </x-button>

        <p class="text-center text-sm text-gray-600">
            Đã có tài khoản? 
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                Đăng nhập
            </a>
        </p>
    </form>
</x-card>
@endsection
