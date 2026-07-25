<?php

/*
|--------------------------------------------------------------------------
| Zoom (Server-to-Server OAuth)
|--------------------------------------------------------------------------
| ⚠️ TODO (BỔ SUNG SAU): tạo app Server-to-Server OAuth tại marketplace.zoom.us,
| thêm scope meeting:write:admin + user:read:admin, rồi dán vào .env:
|     ZOOM_ACCOUNT_ID / ZOOM_CLIENT_ID / ZOOM_CLIENT_SECRET
|     ZOOM_HOST_USER   = email Zoom của giáo viên (host), hoặc 'me'
|     ZOOM_ADMIN_EMAIL = email nhận link mở phòng (start_url)
|
| Khi khóa còn trống → ZoomService báo lỗi rõ. Bật ZOOM_FAKE=true để test luồng
| (đặt lịch → tạo phòng → gửi email) mà KHÔNG gọi Zoom thật.
*/

return [
    'account_id'    => env('ZOOM_ACCOUNT_ID', ''),
    'client_id'     => env('ZOOM_CLIENT_ID', ''),
    'client_secret' => env('ZOOM_CLIENT_SECRET', ''),

    'host_user'     => env('ZOOM_HOST_USER', 'me'),
    'admin_email'   => env('ZOOM_ADMIN_EMAIL', env('MAIL_FROM_ADDRESS')),

    // 🧪 Giả lập: không gọi Zoom, sinh link giả để test.
    'fake'          => filter_var(env('ZOOM_FAKE', false), FILTER_VALIDATE_BOOL),

    'timezone'      => 'Asia/Ho_Chi_Minh',
    'duration'      => (int) env('ZOOM_DURATION', 90), // phút

    'oauth_url'     => 'https://zoom.us/oauth/token',
    'base_url'      => 'https://api.zoom.us/v2',
];
