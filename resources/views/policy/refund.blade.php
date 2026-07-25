@extends('layouts.marketing')

@section('title', 'Chính sách thanh toán & hoàn tiền')
@section('meta_description', 'Chính sách thanh toán và hoàn tiền khi đăng ký gói học hoặc phí chấm bài tại Milaedu.')

@section('content')
<section class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-6">Chính sách thanh toán &amp; hoàn tiền</h1>

    <div class="space-y-5 text-slate-700 leading-relaxed">
        <p>
            Khi thanh toán để đăng ký gói học hoặc phí chấm bài tại Milaedu, học viên
            đồng ý với các điều khoản dưới đây.
        </p>

        <div class="rounded-2xl bg-red-50 border border-red-200 p-5">
            <h2 class="font-bold text-red-800 mb-1">Không hoàn tiền</h2>
            <p class="text-red-700">
                Mọi khoản thanh toán đã hoàn tất <strong>không được hoàn lại</strong> dưới
                bất kỳ hình thức nào, kể cả khi tài khoản chưa sử dụng hết thời hạn hoặc
                chưa dùng hết tính năng. Vui lòng cân nhắc kỹ gói học trước khi thanh toán.
            </p>
        </div>

        <ul class="list-disc pl-5 space-y-2">
            <li>Tài khoản được kích hoạt ngay sau khi hệ thống ghi nhận thanh toán thành công.</li>
            <li>Thời hạn được cộng dồn nếu gia hạn bằng cùng một email.</li>
            <li>Phí chấm bài áp dụng cho mỗi lần gửi bài cho giáo viên chấm.</li>
            <li>Thông tin đăng nhập được gửi về email đã đăng ký — vui lòng kiểm tra kỹ email.</li>
        </ul>

        <p class="text-slate-500">
            Nếu có thắc mắc về giao dịch, vui lòng liên hệ Milaedu qua thông tin ở chân trang.
        </p>
    </div>

    <a href="{{ route('register') }}" class="mt-8 inline-flex items-center px-6 py-3 rounded-xl bg-blue-700 text-white font-bold hover:bg-blue-800 transition-colors">
        Chọn gói &amp; đăng ký
    </a>
</section>
@endsection
