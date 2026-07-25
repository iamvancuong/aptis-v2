<?php

/**
 * Cấu hình SEO tập trung — sửa 1 chỗ, áp cho toàn site.
 *
 * Mọi giá trị đọc được từ env để production ghi đè mà không sửa code. Tên giảng
 * viên đặt ở đây để dùng trong thẻ meta + structured data (nội dung thật, hợp lệ),
 * KHÔNG phải kỹ thuật giấu chữ cho bot.
 */
return [
    // Tên thương hiệu hiển thị trên tab, OG, structured data.
    'site_name' => env('SEO_SITE_NAME', 'Milaedu'),

    // Tiêu đề mặc định (trang không tự set). ~50–60 ký tự là đẹp cho Google.
    'default_title' => env('SEO_DEFAULT_TITLE', 'Milaedu — Luyện thi Aptis online có chấm chữa'),

    // Hậu tố ghép sau tiêu đề từng trang: "Đăng ký · Milaedu".
    'title_suffix' => env('SEO_TITLE_SUFFIX', 'Milaedu'),

    // Mô tả mặc định (~150–160 ký tự). Chứa từ khóa tự nhiên.
    'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'Nền tảng luyện thi Aptis online: đề thi thử sát thật, chấm chữa Writing & Speaking chi tiết, lộ trình bám sát mục tiêu điểm. Học mọi lúc, mọi nơi.'),

    // Từ khóa nền (dùng cho meta keywords + gợi ý nội dung). Tên giảng viên nằm ở
    // đây như một cụm từ khóa thật gắn với thương hiệu.
    'keywords' => env('SEO_KEYWORDS', 'luyện thi Aptis, Aptis online, luyện thi Aptis cùng cô Dung, cô Dung Aptis, Aptis Speaking, Aptis Writing, khóa học Aptis, thi thử Aptis'),

    // Ảnh chia sẻ mạng xã hội (OG/Twitter). Đặt file thật vào public/ sau.
    'og_image' => env('SEO_OG_IMAGE', '/images/og-default.png'),

    'locale' => env('SEO_LOCALE', 'vi_VN'),
    'twitter_card' => 'summary_large_image',

    // Thông tin giảng viên cho structured data Person + trang giới thiệu.
    // Đây là NỘI DUNG THẬT, người dùng thấy được ở mục "Về giảng viên"/footer.
    'instructor' => [
        'name'        => env('SEO_INSTRUCTOR_NAME', 'Cô Dung'),
        'job_title'   => env('SEO_INSTRUCTOR_TITLE', 'Giảng viên luyện thi Aptis'),
        // 2–3 dòng bio thật — bạn cập nhật nội dung chính xác sau.
        'bio'         => env('SEO_INSTRUCTOR_BIO', 'Nhiều năm luyện thi Aptis, trực tiếp chấm chữa Writing và Speaking cho học viên đạt mục tiêu điểm.'),
    ],

    // Liên hệ (dùng cho structured data + footer). Đọc từ Setting/env nếu có.
    'contact' => [
        'email'   => env('SEO_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'milaedu.hn@gmail.com')),
        'hotline' => env('SEO_CONTACT_HOTLINE', ''),
    ],
];
