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

        // Từ 5 từ trở lên xử lý như câu (dịch + ghi chú ngữ pháp) thay vì tra
        // kiểu từ điển.
        'phrase_word_threshold' => 5,

        'model' => env('VOCAB_AI_MODEL', 'gpt-4o-mini'),

        // Số thẻ tối đa mỗi phiên ôn tập.
        'review_batch' => (int) env('VOCAB_REVIEW_BATCH', 20),
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
