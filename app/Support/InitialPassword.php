<?php

namespace App\Support;

/**
 * Mật khẩu khởi tạo cho tài khoản mới (mua gói / admin tạo tay / import).
 *
 * BẢO MẬT: trước 10/2026 mọi tài khoản mới đều dùng chung `12345678`. Ai biết
 * email người mua là đăng nhập trước được, rồi màn "đổi mật khẩu lần đầu" (không
 * hỏi mật khẩu cũ) cho phép chiếm luôn tài khoản đã trả tiền. Giờ mỗi tài khoản
 * một mật khẩu ngẫu nhiên, gửi riêng qua email / hiện cho admin một lần.
 *
 * Bỏ các ký tự dễ nhầm khi gõ lại từ email/Zalo (0/o, 1/l/i).
 */
class InitialPassword
{
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public static function make(int $length = 10): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';

        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }
}
