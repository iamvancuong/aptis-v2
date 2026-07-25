<x-mail::message>
# Buổi hướng dẫn sắp diễn ra 🎓

Chào bạn, đây là link vào buổi hướng dẫn của Milaedu:

- **Thời gian:** {{ $sessionAt->format('H:i') }} — Thứ 7 ngày {{ $sessionAt->format('d/m/Y') }}
@if($passcode)
- **Mật khẩu phòng:** {{ $passcode }}
@endif

<x-mail::button :url="$joinUrl">
Vào phòng học
</x-mail::button>

Nếu nút không mở được, sao chép liên kết:
{{ $joinUrl }}

Vui lòng vào bằng đúng email đã đăng ký. Hẹn gặp bạn!<br>
— Đội ngũ Milaedu
</x-mail::message>
