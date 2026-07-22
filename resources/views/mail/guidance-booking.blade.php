<x-mail::message>
# Xác nhận buổi hướng dẫn 🎓

Bạn đã đặt lịch tham gia buổi hướng dẫn học của Milaedu.

- **Tài khoản:** {{ $email }}
- **Thời gian:** {{ $sessionAt->format('H:i') }} — Thứ 7 ngày {{ $sessionAt->format('d/m/Y') }}

<x-mail::button :url="$zoomLink">
Vào phòng Zoom
</x-mail::button>

Nếu nút không mở được, hãy sao chép liên kết sau:
{{ $zoomLink }}

Hẹn gặp bạn tại buổi học!<br>
— Đội ngũ Milaedu
</x-mail::message>
