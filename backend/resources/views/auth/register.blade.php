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
