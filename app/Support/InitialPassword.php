<?php

namespace App\Support;

/**
 * Mật khẩu khởi tạo cho tài khoản mới (mua gói / admin tạo tay / import).
 *
 * 14/10/2026: quay lại mật khẩu mặc định chung `12345678` theo yêu cầu vận hành —
 * mật khẩu ngẫu nhiên gửi qua email hay rơi vào Spam, học viên không tìm thấy,
 * giáo viên mất thời gian nhắn lại. Đánh đổi đã biết: ai biết email tài khoản mới
 * (chưa đổi mật khẩu) là đăng nhập được, nên vẫn giữ `must_change_password`
 * để học viên đổi ngay ở lần đăng nhập đầu.
 */
class InitialPassword
{
    public const DEFAULT = '12345678';

    public static function make(): string
    {
        return self::DEFAULT;
    }
}
