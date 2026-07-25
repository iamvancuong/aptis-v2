@extends('layouts.auth')

@section('title', 'Đăng nhập')
@section('noindex', '1')

@section('content')
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
    <div class="mb-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Đăng nhập</h1>
        <p class="text-slate-500 mt-1">Tiếp tục lộ trình luyện thi Aptis của bạn.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-1">
        @csrf

        <x-input label="Email" name="email" type="email" required placeholder="email@example.com" :error="$errors->first('email')" />
        <x-input label="Mật khẩu" name="password" type="password" required placeholder="••••••••" :error="$errors->first('password')" />

        <label class="flex items-center gap-2 text-sm text-slate-600 mb-5">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600">
            Ghi nhớ đăng nhập
        </label>

        <x-button type="submit" class="w-full py-3">Đăng nhập</x-button>
    </form>

    <p class="text-center text-sm text-slate-600 mt-6">
        Chưa có tài khoản?
        <a href="{{ route('register') }}" class="text-blue-700 font-medium hover:underline">Đăng ký ngay</a>
    </p>
</div>
@endsection
