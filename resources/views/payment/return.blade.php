@extends('layouts.guest')

@section('title', 'Đã nhận thanh toán - Milaedu')

@section('content')
<x-card>
    <div class="text-center">
        <div class="text-5xl mb-3">✅</div>
        <h2 class="text-2xl font-bold text-gray-900">Cảm ơn bạn!</h2>
        @if($order->type === \App\Models\Order::TYPE_GRADING)
            <p class="text-gray-600 mt-3 text-sm leading-relaxed">
                Chúng tôi đang xác nhận thanh toán cho đơn <strong>{{ $order->order_code }}</strong>.
                Ngay khi hoàn tất, <strong>bài của bạn sẽ được gửi tới giáo viên chấm</strong>.
                Bạn có thể xem kết quả trong phần Lịch sử sau khi giáo viên chấm xong.
            </p>
            <a href="{{ route('dashboard') }}" class="mt-6 inline-block py-3 px-6 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                Về trang chủ
            </a>
        @else
            <p class="text-gray-600 mt-3 text-sm leading-relaxed">
                Chúng tôi đang xác nhận thanh toán cho đơn <strong>{{ $order->order_code }}</strong>.
                Ngay khi hoàn tất, <strong>thông tin đăng nhập sẽ được gửi về email {{ $order->email }}</strong>
                (thường trong vài phút). Vui lòng kiểm tra cả hộp thư spam.
            </p>
            <a href="{{ route('login') }}" class="mt-6 inline-block py-3 px-6 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                Tới trang đăng nhập
            </a>
        @endif
    </div>
</x-card>
@endsection
