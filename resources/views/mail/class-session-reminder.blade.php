<x-mail::message>
# Sắp tới giờ học 👋

Buổi học online của bạn sắp bắt đầu:

**{{ $title }}**
{{ $timeLabel }}

@if($moTa)
{{ $moTa }}
@endif

Cửa lớp mở trước giờ học {{ \App\Models\ClassSession::JOIN_EARLY_MINUTES }} phút. Bấm nút bên dưới, đăng nhập rồi chọn **Vào lớp**.

<x-mail::button :url="$classUrl">
Tới trang lớp học
</x-mail::button>

Nếu nút không bấm được, mở địa chỉ này: {{ $classUrl }}

---

**Lưu ý:** đường vào lớp gắn với tài khoản của bạn. Chuyển tiếp email này cho người khác cũng không dùng được — họ không đăng nhập được vào tài khoản của bạn. Mỗi lượt vào lớp đều được ghi lại.

Hẹn gặp bạn trong lớp,
{{ config('app.name') }}
</x-mail::message>
