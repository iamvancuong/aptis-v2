<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Giới hạn thiết bị cho một tài khoản
    |--------------------------------------------------------------------------
    |
    | Mục tiêu: chặn chia sẻ tài khoản, nhưng KHÔNG phạt học viên thật chỉ vì họ
    | đổi máy hay mất cookie.
    |
    | Bài học đã trả giá: bản đầu đếm TOÀN BỘ dòng `login_sessions` từng tạo, mà
    | dòng chỉ mất khi bấm Đăng xuất. Mỗi lần mở tab ẩn danh / xoá cookie / đổi
    | trình duyệt lại sinh một "thiết bị" mới vĩnh viễn, nên một người ngồi một
    | máy vẫn bị khoá. Nay chỉ đếm phiên còn HOẠT ĐỘNG trong `activity_window`.
    |
    */

    // Số thiết bị được dùng ĐỒNG THỜI. Vượt số này mới tính là vi phạm.
    'max_devices' => (int) env('DEVICE_MAX', 2),

    // Phiên không phát sinh request trong ngần này giờ thì coi như đã rời đi và
    // KHÔNG còn chiếm suất. Càng ngắn càng dễ chia sẻ luân phiên; càng dài càng
    // dễ phạt oan người đổi máy trong ngày.
    'activity_window_hours' => (int) env('DEVICE_ACTIVITY_WINDOW_HOURS', 6),

    // Đủ ngần này vi phạm thì khoá tài khoản.
    // 2 = "lần đầu cảnh báo, cố tình lần nữa thì khoá".
    'block_after_violations' => (int) env('DEVICE_BLOCK_AFTER', 2),

    // Vi phạm cũ hơn ngần này ngày thì bỏ qua, đếm lại từ đầu.
    // Không có phần này thì một lần tháng 3 cộng một lần tháng 8 cũng thành khoá,
    // mà đó không phải "cố tình" — đó là hai lần xui cách nhau nửa năm.
    'violation_reset_days' => (int) env('DEVICE_VIOLATION_RESET_DAYS', 30),

    // Dọn phiên không hoạt động quá ngần này ngày (lệnh `sessions:prune`).
    // Chỉ để bảng khỏi phình; không ảnh hưởng phép đếm vì đã lọc theo cửa sổ trên.
    'prune_after_days' => (int) env('DEVICE_PRUNE_AFTER_DAYS', 30),

];
