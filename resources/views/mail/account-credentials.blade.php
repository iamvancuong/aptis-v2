<x-mail::message>
# Xin chào 👋

@if($isNew)
Tài khoản Milaedu của bạn đã được tạo và kích hoạt. Thông tin đăng nhập:

- **Email:** {{ $email }}
- **Mật khẩu:** {{ $password }}

Vì lý do bảo mật, bạn sẽ được yêu cầu **đổi mật khẩu ngay lần đăng nhập đầu tiên**.
@else
Tài khoản của bạn đã được **gia hạn thành công**. Bạn tiếp tục đăng nhập như bình thường.

- **Email:** {{ $email }}
@endif

@if($expiresAt)
**Hạn sử dụng đến:** {{ $expiresAt->format('d/m/Y') }}
@endif

<x-mail::button :url="$loginUrl">
Đăng nhập ngay
</x-mail::button>

Chúc bạn học tốt!<br>
— Đội ngũ Milaedu
</x-mail::message>
