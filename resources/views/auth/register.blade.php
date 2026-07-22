@extends('layouts.guest')

@section('title', 'Đăng ký - Milaedu')
@section('container', 'max-w-lg')

@section('content')
<div x-data="{
        pkg: '{{ $selected }}',
        qty: 1,
        prices: {{ Illuminate\Support\Js::from(collect($packages)->map(fn($p) => ['price' => $p['price'], 'unit' => $p['unit'], 'max' => $p['max'], 'days' => $p['days']])) }},
        get total() { return this.prices[this.pkg].price * this.qty; },
        fmt(n) { return n.toLocaleString('vi-VN') + 'đ'; }
     }">

    <div class="text-center mb-6">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-4">
            <div class="w-9 h-9 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-bold">M</div>
            <span class="font-bold text-xl text-slate-800">Mila<span class="text-blue-600">edu</span></span>
        </a>
        <h2 class="text-2xl font-bold text-gray-900">Đăng ký tài khoản</h2>
        <p class="text-gray-500 mt-1 text-sm">Chọn gói học và thanh toán để kích hoạt ngay</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        @csrf

        {{-- Chọn gói --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">1. Chọn gói</label>
            <div class="grid grid-cols-2 gap-3">
                @foreach($packages as $key => $p)
                    <label class="relative cursor-pointer block">
                        <input type="radio" name="package" value="{{ $key }}" x-model="pkg" class="peer sr-only">
                        <div class="h-full rounded-xl border-2 p-4 text-center transition-all border-gray-200 hover:border-blue-300 peer-checked:border-blue-600 peer-checked:bg-blue-50 peer-checked:ring-2 peer-checked:ring-blue-100">
                            @if($p['popular'] ?? false)
                                <span class="absolute -top-2 left-1/2 -translate-x-1/2 bg-emerald-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wide">Phổ biến</span>
                            @endif
                            <div class="font-bold text-gray-900">{{ $p['label'] }}</div>
                            <div class="text-blue-600 font-extrabold text-lg mt-1">{{ number_format($p['price'], 0, ',', '.') }}đ</div>
                            <div class="text-xs text-gray-400">{{ $p['days'] }} ngày</div>
                        </div>
                        {{-- dấu tích khi chọn --}}
                        <svg class="absolute top-2 right-2 w-5 h-5 text-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Số lượng --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">2. Số lượng <span class="font-normal text-gray-400">(mua nhiều — cộng dồn thời hạn)</span></label>
            <div class="flex items-center gap-3">
                <button type="button" @click="qty = Math.max(1, qty - 1)"
                        class="w-11 h-11 rounded-xl border border-gray-300 text-2xl font-bold text-gray-500 hover:bg-gray-50 active:scale-95 transition">−</button>
                <input type="number" name="quantity" x-model.number="qty" min="1" :max="prices[pkg].max"
                       class="w-20 h-11 text-center text-lg font-bold px-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" @click="qty = Math.min(prices[pkg].max, qty + 1)"
                        class="w-11 h-11 rounded-xl border border-gray-300 text-2xl font-bold text-gray-500 hover:bg-gray-50 active:scale-95 transition">+</button>
                <span class="text-sm text-gray-500">= <strong x-text="prices[pkg].days * qty"></strong> ngày</span>
            </div>
            @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Email --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">3. Email nhận tài khoản</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="email@example.com"
                   class="w-full h-11 px-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            <p class="mt-1 text-xs text-gray-400">Thông tin đăng nhập sẽ được gửi về email này sau khi thanh toán thành công.</p>
        </div>

        {{-- Tổng tiền --}}
        <div class="flex items-center justify-between rounded-xl bg-blue-50 border border-blue-100 px-4 py-3">
            <span class="text-gray-600 font-medium">Tổng thanh toán</span>
            <span class="text-2xl font-extrabold text-blue-600" x-text="fmt(total)"></span>
        </div>

        <button type="submit" class="w-full h-12 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 active:scale-[.99] transition shadow-md shadow-blue-500/20">
            Tiếp tục thanh toán
        </button>

        <p class="text-center text-xs text-gray-400">
            Bằng việc thanh toán, bạn đồng ý với
            <a href="{{ route('policy.refund') }}" target="_blank" class="underline hover:text-blue-600">Chính sách không hoàn tiền</a>.
        </p>

        <p class="text-center text-sm text-gray-500 pt-2 border-t border-gray-100">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Đăng nhập</a>
        </p>
    </form>
</div>
@endsection
