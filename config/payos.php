<?php

/*
|--------------------------------------------------------------------------
| Cấu hình PayOS
|--------------------------------------------------------------------------
| ⚠️ TODO (BỔ SUNG SAU): 3 khóa dưới đây lấy sau khi đăng ký PayOS và liên
| kết tài khoản ngân hàng nhận tiền:
|   my.payos.vn → Kênh thanh toán → tạo kênh → lấy Client ID / API Key /
|   Checksum Key → dán vào file .env (KHÔNG commit .env):
|
|       PAYOS_CLIENT_ID=...
|       PAYOS_API_KEY=...
|       PAYOS_CHECKSUM_KEY=...
|
| Khi 3 khóa còn trống, PayosService sẽ báo lỗi rõ ràng thay vì chạy mù —
| nên phần code vẫn build/test được ở môi trường dev bằng payload giả lập.
*/

return [
    'client_id'    => env('PAYOS_CLIENT_ID', ''),
    'api_key'      => env('PAYOS_API_KEY', ''),
    'checksum_key' => env('PAYOS_CHECKSUM_KEY', ''),

    // 🧪 Chế độ giả lập: KHÔNG gọi PayOS thật, hiện nút "giả lập đã thanh toán"
    // để test cả luồng (tạo tài khoản + email) mà không mất tiền. Đặt
    // PAYOS_FAKE=true ở .env local; PRODUCTION để trống/false.
    'fake'         => filter_var(env('PAYOS_FAKE', false), FILTER_VALIDATE_BOOL),

    // Endpoint REST của PayOS (ít khi đổi).
    'base_url'     => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),

    // Trang người dùng quay về sau khi thanh toán (đường dẫn nội bộ).
    'return_path'  => '/thanh-toan/thanh-cong',
    'cancel_path'  => '/thanh-toan/huy',
];
