<?php

/**
 * Cấu hình SEO tập trung — sửa 1 chỗ, áp cho toàn site.
 *
 * Mọi giá trị đọc được từ env để production ghi đè mà không sửa code. Tên giảng
 * viên đặt ở đây để dùng trong thẻ meta + structured data (nội dung thật, hợp lệ),
 * KHÔNG phải kỹ thuật giấu chữ cho bot.
 */
// Công tắc DUY NHẤT cho việc hiện tên giảng viên trên trang công khai (giao diện,
// title, meta, keywords, JSON-LD). Đã đổi ý vài lần (bỏ 10/2026 → hiện lại 08/10 →
// bỏ 10/10) nên gom về một env: SEO_SHOW_INSTRUCTOR=true là hiện lại, khỏi sửa code.
$hienTenGv = (bool) env('SEO_SHOW_INSTRUCTOR', false);
$tenGv = env('SEO_INSTRUCTOR_NAME', 'Cô Dung');

return [
    // Tên thương hiệu hiển thị trên tab, OG, structured data.
    'site_name' => env('SEO_SITE_NAME', 'Milaedu'),

    // Tiêu đề mặc định (trang không tự set). ~50–60 ký tự là đẹp cho Google.
    'default_title' => env('SEO_DEFAULT_TITLE', 'Milaedu — Luyện thi Aptis online có chấm chữa'),

    // Hậu tố ghép sau tiêu đề từng trang: "Đăng ký · Milaedu".
    'title_suffix' => env('SEO_TITLE_SUFFIX', 'Milaedu'),

    // Mô tả mặc định (~150–160 ký tự). Chứa từ khóa tự nhiên.
    'default_description' => env('SEO_DEFAULT_DESCRIPTION', 'Nền tảng luyện thi Aptis online: đề thi thử sát thật, Chấm chữa writing và speaking chi tiết, lộ trình bám sát mục tiêu điểm. Học mọi lúc, mọi nơi.'),

    // Từ khóa nền (dùng cho meta keywords + gợi ý nội dung). Có tên giảng viên
    // chỉ khi bật SEO_SHOW_INSTRUCTOR.
    'keywords' => env('SEO_KEYWORDS', 'luyện thi Aptis, Aptis online, '
        . ($hienTenGv ? 'luyện thi Aptis cùng ' . mb_strtolower($tenGv) . ', ' . mb_strtolower($tenGv) . ' Aptis, ' : '')
        . 'Aptis Speaking, Aptis Writing, khóa học Aptis, thi thử Aptis'),

    // Ảnh chia sẻ mạng xã hội (OG/Twitter). Đặt file thật vào public/ sau.
    'og_image' => env('SEO_OG_IMAGE', '/images/og-default.png'),

    'locale' => env('SEO_LOCALE', 'vi_VN'),
    'twitter_card' => 'summary_large_image',

    // Mã xác minh Google Search Console (cách "HTML tag"). Dán ĐÚNG phần content
    // trong thẻ Google đưa, không dán cả thẻ. Để trống thì không render gì.
    // VD: SEO_GOOGLE_SITE_VERIFICATION=abc123xyz...
    'google_site_verification' => env('SEO_GOOGLE_SITE_VERIFICATION', ''),

    // Thông tin giảng viên cho trang giới thiệu (+ structured data Person khi hiện tên).
    'instructor' => [
        'show'        => $hienTenGv,
        'name'        => $tenGv,
        // Cách gọi trên trang công khai: tên thật khi bật, chữ trung tính khi tắt.
        'display'     => $hienTenGv ? $tenGv : 'đội ngũ giảng viên Milaedu',
        'job_title'   => env('SEO_INSTRUCTOR_TITLE', 'Giảng viên luyện thi Aptis'),
        // 2–3 dòng bio thật — bạn cập nhật nội dung chính xác sau.
        'bio'         => env('SEO_INSTRUCTOR_BIO', 'Nhiều năm luyện thi Aptis, trực tiếp chấm chữa Writing cho học viên đạt mục tiêu điểm.'),
        // Ảnh chân dung THẬT. Bỏ trống → web tự vẽ ô chữ cái đầu làm placeholder.
        // Đặt file vào `public/images/` rồi khai: SEO_INSTRUCTOR_PHOTO=/images/co-dung.jpg
        'photo'       => env('SEO_INSTRUCTOR_PHOTO', ''),
    ],

    // Liên hệ (dùng cho structured data + footer). Đọc từ Setting/env nếu có.
    'contact' => [
        'email'    => env('SEO_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'milaedu.hn@gmail.com')),
        'hotline'  => env('SEO_CONTACT_HOTLINE', ''),
        // Nhóm cộng đồng Facebook (hiện ở footer + có thể dùng cho sameAs structured data).
        'facebook' => env('SEO_FACEBOOK_GROUP', 'https://www.facebook.com/groups/351705076734456/'),
    ],
];
