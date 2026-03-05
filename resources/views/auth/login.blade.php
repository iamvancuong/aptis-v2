@extends('layouts.guest')

@section('title', 'Đăng nhập - APTIS Practice')

@section('content')
<x-card>
    <div class="text-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Đăng nhập</h2>
        <p class="text-gray-600 mt-1">Chào mừng bạn trở lại</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

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
        />

        <div class="flex items-center justify-between mb-4">
            <label class="flex items-center">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Ghi nhớ đăng nhập</span>
            </label>
        </div>

        <x-button type="submit" class="w-full mb-4">
            Đăng nhập
        </x-button>

        <p class="text-center text-sm text-gray-600">
            Chưa có tài khoản? 
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                Đăng ký ngay
            </a>
        </p>
    </form>
</x-card>
@endsection
