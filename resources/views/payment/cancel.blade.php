@extends('layouts.guest')

@section('title', 'Đã hủy thanh toán - Milaedu')

@section('content')
<x-card>
    <div class="text-center">
        <div class="text-5xl mb-3">✖️</div>
        <h2 class="text-2xl font-bold text-gray-900">Đã hủy thanh toán</h2>
        <p class="text-gray-600 mt-3 text-sm">
            Đơn <strong>{{ $order->order_code }}</strong> chưa được thanh toán. Bạn có thể chọn lại gói bất cứ lúc nào.
        </p>
        <a href="{{ route('register') }}" class="mt-6 inline-block py-3 px-6 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
            Chọn lại gói
        </a>
    </div>
</x-card>
@endsection
