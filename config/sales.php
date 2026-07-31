<?php

/*
|--------------------------------------------------------------------------
| Cộng tác viên / Sale giới thiệu (referral)
|--------------------------------------------------------------------------
| Mỗi sale có 1 MÃ ngắn (M1, M2…) dùng trong link giới thiệu và nội dung
| chuyển khoản. Sale KHÔNG cần tài khoản web — admin lấy link ở trang Doanh số
| rồi gửi cho sale.
|
| Thêm sale mới = thêm 1 dòng vào đây rồi deploy lại. Mã nên NGẮN (≤ 6 ký tự,
| chữ + số) vì còn nhét vào `description` PayOS (giới hạn 25 ký tự).
|
| Link mỗi sale (sinh tự động ở trang Doanh số):
|   /dk/M1/thang  ·  /dk/M1/tuan  ·  /dk/M2/thang  ·  /dk/M2/tuan
*/

return [
    'reps' => [
        'M1' => ['name' => 'Nguyệt Anh', 'active' => true],   // 🔴 đổi 'name' thành tên thật
        'M2' => ['name' => 'Trinh', 'active' => true],   // 🔴 đổi 'name' thành tên thật
    ],
];
