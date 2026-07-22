<?php

/*
|--------------------------------------------------------------------------
| Bảng giá Milaedu — nguồn sự thật duy nhất
|--------------------------------------------------------------------------
| Trang chủ, flow đăng ký và bảng đơn hàng đều đọc từ đây. Sửa giá 1 chỗ.
| Giá TUYẾN TÍNH: thành tiền = price × quantity. KHÔNG giảm giá.
*/

return [
    // Gói đăng ký tài khoản. Giá lấy từ .env (PRICE_WEEK / PRICE_MONTH) để đổi
    // không cần sửa code. Thành tiền = price × số lượng (cộng dồn thời hạn).
    'packages' => [
        'week' => [
            'label'    => 'Gói 2 Tuần',
            'unit'     => 'gói',
            'price'    => (int) env('PRICE_WEEK', 399000), // đồng / gói 2 tuần
            'days'     => 14,     // 2 tuần
            'min'      => 1,
            'max'      => 26,     // trần kỹ thuật
            'popular'  => false,
        ],
        'month' => [
            'label'    => 'Gói 1 Tháng',
            'unit'     => 'gói',
            'price'    => (int) env('PRICE_MONTH', 699000), // đồng / gói 1 tháng
            'days'     => 30,     // 1 tháng
            'min'      => 1,
            'max'      => 12,
            'popular'  => true,
        ],
    ],

    // Phí gửi 1 bài cho giáo viên chấm tay (Speaking / Writing).
    'grading_price' => (int) env('PRICE_GRADING', 99000),

    // Chia doanh thu ĐĂNG KÝ (không tính doanh thu chấm bài).
    // Doanh thu chấm bài để riêng, dành cho Cô Dung (xem màn Doanh số).
    'revenue_split' => [
        'co_dung' => 40,
        'cuong'   => 30,
        'con_lai' => 30,
    ],
];
