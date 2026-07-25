<x-mail::message>
# Đã đặt lịch buổi hướng dẫn ✅

Bạn đã đặt lịch tham gia buổi hướng dẫn học của Milaedu.

- **Tài khoản:** {{ $email }}
- **Thời gian:** {{ $sessionAt->format('H:i') }} — Thứ 7 ngày {{ $sessionAt->format('d/m/Y') }}

**Link vào phòng Zoom sẽ được gửi qua email này trước buổi học.** Vui lòng
kiểm tra email (kể cả hộp thư spam) gần tới ngày học.

Hẹn gặp bạn tại buổi học!<br>
— Đội ngũ Milaedu
</x-mail::message>
