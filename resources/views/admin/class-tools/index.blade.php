@extends('layouts.admin')

@section('title', 'Kiểm tra lớp online - Admin')
@section('header', 'Kiểm tra lớp online')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.class-sessions.index') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Quay lại buổi học
    </a>
</div>

@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">{{ $errors->first() }}</div>
@endif

{{-- Nói thẳng giới hạn: các lệnh ở đây chỉ ĐỌC. Admin cần tin được điều đó thì
     mới dám bấm giữa buổi dạy. --}}
<div class="mb-6 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
    Mọi công cụ ở trang này <strong>chỉ đọc</strong> — không tạo, không sửa, không xoá, không gửi email.
    Bấm bao nhiêu lần cũng được, kể cả đang giữa buổi dạy.
</div>

@if($ketQua !== null)
    <x-card class="mb-6" title="Kết quả: {{ $daChay }}">
        {{-- `pre` + overflow riêng: kết quả có dòng dài (link Meet, danh sách email)
             và trang không được cuộn ngang theo. --}}
        <pre class="overflow-x-auto text-xs font-mono bg-gray-900 text-white p-4 rounded-lg leading-relaxed whitespace-pre">{{ trim($ketQua) }}</pre>
    </x-card>
@endif

<div class="space-y-4">
    @foreach($congCu as $khoa => $c)
        <x-card>
            <div class="flex flex-col sm:flex-row items-start justify-between gap-3">
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-gray-800">{{ $c['ten'] }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $c['mo_ta'] }}</p>
                </div>

                <form method="POST" action="{{ route('admin.class-tools.run') }}" class="shrink-0">
                    @csrf
                    {{-- Trình duyệt chỉ gửi KHOÁ, không gửi tên lệnh. Tên lệnh và
                         cờ --dry-run nằm cứng trong controller. --}}
                    <input type="hidden" name="cong_cu" value="{{ $khoa }}">

                    @if($c['can_nhap'] === 'user')
                        <input type="email" name="user" value="{{ old('user') }}"
                               placeholder="email học viên (tuỳ chọn)"
                               class="w-full max-w-md mb-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @endif

                    @if($c['can_nhap'] === 'emails')
                        <input type="number" name="session" value="{{ old('session') }}" min="1"
                               placeholder="ID buổi (tuỳ chọn)"
                               class="w-full max-w-md mb-2 px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @endif

                    <button type="submit"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        Chạy
                    </button>

                    @if($c['can_nhap'] === 'emails')
                        <textarea name="emails" rows="4"
                                  placeholder="Dán danh sách Gmail, hoặc nguyên nội dung file CSV điểm danh Google — hệ thống tự tách email."
                                  class="w-full max-w-lg mt-2 px-3 py-2 text-xs font-mono text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('emails') }}</textarea>
                    @endif
                </form>
            </div>
        </x-card>
    @endforeach
</div>

<div class="mt-6 text-xs text-gray-500 space-y-1">
    <p><strong>Không có ở đây:</strong> lệnh gửi email nhắc giờ học (gửi thật cho hàng trăm người — cron đã tự lo) và mọi lệnh ghi dữ liệu. Chúng chỉ chạy được từ cPanel Terminal.</p>
    <p><strong>Nhắc lại giới hạn quan trọng:</strong> không công cụ nào ở đây đọc được phía Google. Ai thật sự ngồi trong phòng Meet chỉ biết qua file CSV điểm danh — dán nó vào ô “Gmail này là ai?”.</p>
</div>
@endsection
