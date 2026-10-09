@extends('layouts.app')

@section('title', 'Đổi mật khẩu')

@section('content')
<div class="max-w-md mx-auto mt-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Đổi mật khẩu</h2>
            @if(auth()->user()->must_change_password)
                <p class="text-sm text-gray-500 mt-1">Vì bảo mật, vui lòng đặt mật khẩu mới trước khi tiếp tục.</p>
            @else
                <p class="text-sm text-gray-500 mt-1">Nhập mật khẩu hiện tại và mật khẩu mới (tối thiểu 8 ký tự).</p>
            @endif
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            @unless(auth()->user()->must_change_password)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required autofocus autocomplete="current-password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endunless

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu mới</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                Lưu mật khẩu mới
            </button>
        </form>
    </div>
</div>
@endsection
