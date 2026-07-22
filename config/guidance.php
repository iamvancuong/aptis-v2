<?php

/*
|--------------------------------------------------------------------------
| Buổi hướng dẫn học 19h30 thứ 7 hàng tuần
|--------------------------------------------------------------------------
| ⚠️ TODO (P7): 'zoom_link' hiện là link GIẢ dùng chung. P7 sẽ thay bằng cơ chế
| sinh mã phòng riêng theo từng ngày (chống học chui) và gửi cho đúng nhóm user
| đặt buổi đó.
*/

return [
    'weekday'    => 6,        // 6 = thứ 7 (Carbon::SATURDAY)
    'time'       => '19:30',
    'time_label' => '19h30 thứ 7 hàng tuần',

    // Link Zoom giả — thay ở P7.
    'zoom_link'  => env('GUIDANCE_ZOOM_LINK', 'https://zoom.us/j/0000000000?pwd=milaedu-demo'),
];
