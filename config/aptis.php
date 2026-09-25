<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lớp học online — CÔNG TẮC TẠM TẮT
    |--------------------------------------------------------------------------
    | Tắt = học viên không thấy menu "Lớp học", không thấy thẻ "lớp sắp tới" trên
    | dashboard, mọi URL `/lop-hoc*` trả 404, và cron KHÔNG gửi email nhắc giờ.
    |
    | Cố ý KHÔNG xoá code: tính năng chỉ đang hoãn (Pha 0 đã chạy được — xem §23).
    | Phía ADMIN vẫn dùng bình thường (`/admin/class-sessions`, `/admin/class-groups`)
    | để chuẩn bị buổi học trước khi mở lại cho học viên.
    |
    | Mặc định TẮT: quên khai `.env` thì tính năng vẫn ẩn, an toàn hơn là lộ ra.
    | Bật lại: đặt `CLASSES_ENABLED=true` trong `.env` rồi chạy lại `config:cache`
    | (production đã cache config nên sửa `.env` không thôi sẽ không ăn).
    */
    'classes_enabled' => (bool) env('CLASSES_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Tra từ bằng AI + Sổ tay từ vựng
    |--------------------------------------------------------------------------
    | `enabled` = false thì học viên không bôi chọn được trong bài, mọi URL
    | `/tu-vung*` trả 404 và endpoint tra từ từ chối. Dùng để tắt nhanh khi chi
    | phí API vọt bất thường mà không phải gỡ code hay deploy lại.
    |
    | CỐ Ý không có công tắc bật trong phần thi thử: tra được từ lúc thi thì
    | điểm Reading mất hết ý nghĩa đánh giá. Muốn đổi thì phải sửa code, để
    | quyết định đó có người rà lại.
    |
    | Đổi `.env` ở production nhớ chạy lại `config:cache`.
    */
    'vocab' => [
        'enabled' => (bool) env('VOCAB_LOOKUP_ENABLED', true),

        // Hạn mức lượt tra mỗi học viên mỗi ngày. Lượt trúng kho đệm vẫn tính,
        // vì hạn mức này còn để chặn spam chứ không chỉ để chặn tiền API.
        'daily_limit' => (int) env('VOCAB_DAILY_LIMIT', 60),

        // Bôi dài hơn ngần này coi như chọn nhầm cả đoạn → từ chối trước khi
        // gọi API. 300 ký tự đủ cho câu dài nhất trong đề APTIS.
        'max_chars' => (int) env('VOCAB_MAX_CHARS', 300),

        // KHÔNG còn ngưỡng số từ: bôi 1 từ là tra từ điển, bôi từ 2 từ trở lên
        // là dịch nguyên cụm. Ngưỡng 5 từ cũ khiến "I cycle to work" (4 từ) bị
        // tra như một từ đơn và AI chỉ dịch "cycle" → "đạp xe".

        'model' => env('VOCAB_AI_MODEL', 'gpt-4o-mini'),

        // Số thẻ tối đa mỗi phiên ôn tập.
        'review_batch' => (int) env('VOCAB_REVIEW_BATCH', 20),

        // Số thư mục tự tạo tối đa mỗi học viên — chặn spam, không phải giới
        // hạn nghiệp vụ.
        'max_folders' => 50,

        // Số từ tối đa mỗi tệp PDF luyện viết (dompdf dựng cả tệp trong RAM).
        'pdf_max_items' => 200,

        /*
         | Lịch ôn kiểu Anki.
         |
         | learning_steps: các bước (phút) của thẻ mới hoặc thẻ vừa quên. Nhớ →
         | sang bước kế, Mơ hồ → lặp lại bước hiện tại, Quên → về bước đầu.
         | Qua hết các bước thì "tốt nghiệp" sang ôn theo ngày.
         */
        'srs' => [
            'learning_steps' => [1, 5, 10, 60],
            'graduating_interval' => 1,   // ngày, sau khi qua bước cuối
            'starting_ease' => 2500,      // hệ số giãn ×1000 (2.5)
            'min_ease' => 1300,
            'hard_multiplier' => 1.2,
            'max_interval' => 365,        // ngày
            'mastered_interval' => 21,    // ngày — Anki gọi là thẻ "mature"
            // Thẻ đang học sẽ quay lại trong chính phiên ôn nếu tới hạn trong
            // khoảng này (phút).
            'learn_ahead' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Section Blueprints
    |--------------------------------------------------------------------------
    | Define which parts appear in a full mock test for each skill.
    | Parts can repeat (e.g. Reading Part 2 appears twice).
    */
    'exam_sections' => [
        'reading'   => [1, 2, 3, 4],
        'listening' => [1, 2, 3, 4],
        'writing'   => [1, 2, 3, 4],
        'speaking'  => [1, 2, 3, 4],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Duration (minutes)
    |--------------------------------------------------------------------------
    */
    'exam_duration' => [
        'reading'   => 35,
        'listening' => 35,
        'writing'   => 50,
        'speaking'  => 12,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Part Counts
    |--------------------------------------------------------------------------
    | Define how many sets/questions should be picked for each part.
    | Defaults to 1 if not specified.
    |*/
    'exam_part_counts' => [
        'listening' => [
            1 => 13,   // 13 random MC questions
            2 => 1,    // 1 random speaker-matching question
            3 => 1,    // 1 random man/woman/both question
            4 => 2,    // 2 random passage questions
        ],
        'reading' => [
            1 => 1,
            2 => 2,
            3 => 1,
            4 => 1,
        ],
    ],
];
