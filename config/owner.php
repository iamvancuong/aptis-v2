<?php

/*
|--------------------------------------------------------------------------
| Tài khoản CHỦ SỞ HỮU (role owner)
|--------------------------------------------------------------------------
| Đọc từ .env nhưng KHAI Ở ĐÂY để tránh bẫy config:cache: khi production đã
| `config:cache`, mọi lời gọi env() NGOÀI thư mục config/ đều trả về null (file
| .env không còn được nạp). OwnerAccountSeeder vì thế phải đọc qua config()
| chứ không phải env() trực tiếp — giá trị được "nướng" vào cache lúc chạy
| config:cache (nên nhớ set .env TRƯỚC khi cache).
*/

return [
    'email'    => env('OWNER_EMAIL'),
    'password' => env('OWNER_PASSWORD'),
];
