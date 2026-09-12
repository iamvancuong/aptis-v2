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

    // Giá trị QUY ĐỔI của một học viên do admin thêm tay (source='manual'). Nhóm
    // này trả tiền offline (tiền mặt / chuyển khoản thẳng) nên KHÔNG có trong
    // bảng `orders`. Chỉ dùng cho phần thống kê doanh thu TỔNG của chủ sở hữu
    // (role owner) ở màn Doanh số — không ai khác thấy con số này. 2 triệu/HV.
    'manual_student_value' => (int) env('MANUAL_STUDENT_VALUE', 2000000),

    // Chia doanh thu ĐĂNG KÝ (không tính doanh thu chấm bài).
    // Doanh thu chấm bài để riêng, dành cho Cô Dung (xem màn Doanh số).
    //
    // Đổi 01/09/2026: bỏ phần 30% của Cường, gộp vào Cô Dung thành 70%.
    // Phần "còn lại" giữ nguyên 30%.
    //
    // ⚠️ Hai số này phải cộng lại đúng 100 — không có ràng buộc nào ở code kiểm
    // hộ, và sai thì bảng vẫn hiện bình thường với tổng chia không khớp doanh thu.
    'revenue_split' => [
        'co_dung' => 70,
        'con_lai' => 30,
    ],
];
