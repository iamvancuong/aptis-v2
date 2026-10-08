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
    | "Gửi giáo viên chấm bài" (đơn chấm phí cho Mock Test Writing/Speaking).
    | Mặc định TẮT — nút đã ẩn ở giao diện, backend cũng chặn. Bật lại: đặt
    | `TEACHER_GRADING_ENABLED=true` rồi `config:cache`.
    */
    'teacher_grading_enabled' => (bool) env('TEACHER_GRADING_ENABLED', false),

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

        // gpt-4.1-mini thay gpt-4o-mini (06/10): đo trên 28 từ trong đề Reading thật,
        // 4o-mini hay kéo chữ trong câu vào nghĩa ("alike" → "cả nhân viên và công
        // ty", "pursue" → "theo đuổi sở thích") dù prompt đã cấm; 4.1-mini sạch cả
        // 28. Đắt hơn ~2,7 lần nhưng vẫn chỉ ~10đ/lượt chưa trúng kho đệm.
        'model' => env('VOCAB_AI_MODEL', 'gpt-4.1-mini'),

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
        // Grammar & Vocabulary: một bộ đề gồm cả 2 phần (25 câu trắc nghiệm + 5 nhóm từ vựng).
        // Chỉ dùng trong Full Test — không có trang thi thử Grammar riêng.
        'grammar'   => [1, 2],
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
        'grammar'   => 25,
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

    /*
    |--------------------------------------------------------------------------
    | Full Test — thi liên tục 5 phần như kỳ thi Aptis thật
    |--------------------------------------------------------------------------
    | `stages`: thứ tự các phần. `default_quota`: số lượt mỗi tài khoản mới
    | (admin tăng riêng từng người ở /admin/full-tests).
    */
    'full_test' => [
        'stages'        => ['speaking', 'listening', 'grammar', 'reading', 'writing'],
        'default_quota' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ngưỡng CEFR theo thang điểm Aptis (0–50) của từng kỹ năng
    |--------------------------------------------------------------------------
    | Điểm Aptis TỐI THIỂU để đạt từng bậc. Đây là ngưỡng THAM KHẢO phổ biến của
    | Aptis General (British Council có thể điều chỉnh theo kỳ) — kết quả trên web
    | là ước tính. Grammar & Vocabulary không xếp bậc CEFR (giống phiếu điểm thật).
    | Bậc tổng (Overall) tính từ tổng điểm 4 kỹ năng (/200) so với tổng ngưỡng.
    */
    'cefr_bands' => [
        'listening' => ['C' => 42, 'B2' => 34, 'B1' => 24, 'A2' => 16, 'A1' => 8,  'A0' => 0],
        'reading'   => ['C' => 46, 'B2' => 38, 'B1' => 26, 'A2' => 16, 'A1' => 8,  'A0' => 0],
        'speaking'  => ['C' => 48, 'B2' => 41, 'B1' => 26, 'A2' => 16, 'A1' => 4,  'A0' => 0],
        'writing'   => ['C' => 48, 'B2' => 40, 'B1' => 26, 'A2' => 18, 'A1' => 6,  'A0' => 0],
    ],
];
