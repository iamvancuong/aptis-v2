@extends('layouts.guest')

@section('title', 'Chính sách thanh toán & hoàn tiền - Milaedu')

@section('content')
<x-card>
    <h1 class="text-2xl font-bold text-gray-900 mb-4">Chính sách thanh toán &amp; hoàn tiền</h1>

    <div class="space-y-4 text-sm text-gray-700 leading-relaxed">
        <p>
            Khi thanh toán để đăng ký gói học hoặc phí chấm bài tại Milaedu, học viên
            đồng ý với các điều khoản dưới đây.
        </p>

        <div class="rounded-xl bg-red-50 border border-red-200 p-4">
            <h2 class="font-bold text-red-800 mb-1">Không hoàn tiền</h2>
            <p class="text-red-700">
                Mọi khoản thanh toán đã hoàn tất <strong>không được hoàn lại</strong> dưới
                bất kỳ hình thức nào, kể cả khi tài khoản chưa sử dụng hết thời hạn hoặc
                chưa dùng hết tính năng. Vui lòng cân nhắc kỹ gói học trước khi thanh toán.
            </p>
        </div>

        <ul class="list-disc pl-5 space-y-1">
            <li>Tài khoản được kích hoạt ngay sau khi hệ thống ghi nhận thanh toán thành công.</li>
            <li>Thời hạn được cộng dồn nếu gia hạn bằng cùng một email.</li>
            <li>Phí chấm bài áp dụng cho mỗi lần gửi bài cho giáo viên chấm.</li>
            <li>Thông tin đăng nhập được gửi về email đã đăng ký — vui lòng kiểm tra kỹ email.</li>
        </ul>

        <p class="text-gray-500">
            Nếu có thắc mắc về giao dịch, vui lòng liên hệ Milaedu qua thông tin trên trang chủ.
        </p>
    </div>

    <a href="{{ route('home') }}" class="mt-6 inline-block text-sm text-blue-600 hover:text-blue-700 font-medium">← Về trang chủ</a>
</x-card>
@endsection
