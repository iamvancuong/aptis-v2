<x-mail::message>
# Mở phòng dạy 👨‍🏫

Buổi hướng dẫn của bạn:

- **Thời gian:** {{ $sessionAt->format('H:i') }} — Thứ 7 ngày {{ $sessionAt->format('d/m/Y') }}
- **Số học viên đăng ký:** {{ count($studentEmails) }}
@if($passcode)
- **Mật khẩu phòng:** {{ $passcode }}
@endif

<x-mail::button :url="$startUrl">
Mở phòng (Host)
</x-mail::button>

@if(count($studentEmails))
**Danh sách học viên:**
@foreach($studentEmails as $e)
- {{ $e }}
@endforeach
@endif

Mẹo chống học chui: bật Waiting Room và chỉ cho vào các email trong danh sách trên.<br>
— Milaedu
</x-mail::message>
