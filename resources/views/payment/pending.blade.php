@extends('layouts.guest')

@section('title', 'Thanh toán - Milaedu')

@section('content')
<x-card>
    <div class="text-center mb-6">
        <div class="text-5xl mb-3">🧾</div>
        <h2 class="text-2xl font-bold text-gray-900">Thanh toán đơn hàng</h2>
        <p class="text-gray-500 text-sm mt-1">Mã đơn: {{ $order->order_code }}</p>
    </div>

    <div class="space-y-3 text-sm">
        <div class="flex justify-between border-b border-gray-100 pb-2">
            <span class="text-gray-500">Nội dung</span>
            <span class="font-semibold text-gray-900">
                @if($order->type === \App\Models\Order::TYPE_GRADING)
                    Phí chấm bài ({{ ucfirst($order->meta['skill'] ?? '') }})
                @else
                    {{ $package['label'] ?? $order->package }}
                @endif
            </span>
        </div>
        @if($order->type !== \App\Models\Order::TYPE_GRADING)
        <div class="flex justify-between border-b border-gray-100 pb-2">
            <span class="text-gray-500">Số lượng</span>
            <span class="font-semibold text-gray-900">{{ $order->quantity }} {{ $package['unit'] ?? '' }}</span>
        </div>
        @endif
        <div class="flex justify-between border-b border-gray-100 pb-2">
            <span class="text-gray-500">Email</span>
            <span class="font-semibold text-gray-900 break-all">{{ $order->email }}</span>
        </div>
        <div class="flex justify-between pt-1">
            <span class="text-gray-600 font-medium">Tổng thanh toán</span>
            <span class="text-2xl font-extrabold text-blue-600">{{ number_format($order->amount, 0, ',', '.') }}đ</span>
        </div>
    </div>

    @if(session('error'))
        <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    @if(config('payos.fake'))
        {{-- 🧪 Chế độ giả lập (chỉ hiện khi PAYOS_FAKE=true) --}}
        <div class="mt-6 rounded-xl bg-purple-50 border border-purple-200 p-4 text-sm text-purple-800">
            <strong>🧪 Chế độ giả lập (không mất tiền).</strong> Bấm nút dưới để mô phỏng
            thanh toán thành công — hệ thống sẽ tạo tài khoản và gửi email y như thật.
        </div>
        <a href="{{ route('payment.dev-fulfill', $order) }}"
           class="mt-4 block text-center py-3 px-4 bg-purple-600 text-white font-bold rounded-xl hover:bg-purple-700 transition-colors">
            Giả lập đã thanh toán ✓
        </a>
    @else
        {{-- ⚠️ Hiển thị khi CHƯA cấu hình khóa PayOS. --}}
        <div class="mt-6 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
            <strong>Đang hoàn thiện cổng thanh toán.</strong> QR PayOS sẽ hiển thị sau
            khi khóa PayOS được cấu hình.
        </div>
    @endif

    <p class="mt-4 text-center text-xs text-gray-400">
        <a href="{{ route('policy.refund') }}" target="_blank" class="underline hover:text-blue-600">Chính sách không hoàn tiền</a>
    </p>

    <a href="{{ route('home') }}" class="mt-3 block text-center text-sm text-blue-600 hover:text-blue-700 font-medium">← Về trang chủ</a>
</x-card>
@endsection
