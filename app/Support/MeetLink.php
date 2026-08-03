<?php

namespace App\Support;

/**
 * Chuẩn hoá link phòng học admin nhập tay.
 *
 * Lý do tồn tại: người dùng copy link từ Google Meet hoặc gõ lại thì rất hay ra
 * `meet.google.com/abc-defg-hij` (không có `https://`), hoặc chỉ mỗi mã phòng
 * `abc-defg-hij`. Cả hai đều rớt luật `url` của Laravel. Bắt admin tự thêm
 * `https://` là đẩy việc máy làm được sang cho người, và mỗi lần lưu hụt là một
 * lần bực.
 */
class MeetLink
{
    /** Mã phòng Google Meet: 3-4-3 chữ cái, ví dụ `bbb-tigq-saf`. */
    private const MA_PHONG = '/^[a-z]{3}-[a-z]{4}-[a-z]{3}$/i';

    public static function normalize(?string $input): ?string
    {
        $link = trim((string) $input);

        if ($link === '') {
            return null;
        }

        // Chỉ dán mỗi mã phòng → dựng lại link đầy đủ.
        if (preg_match(self::MA_PHONG, $link)) {
            return 'https://meet.google.com/' . strtolower($link);
        }

        // Thiếu giao thức → mặc định https. Không đụng vào link đã có scheme
        // (kể cả `http://`): đó là thứ admin cố ý gõ, không phải chỗ để đoán.
        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $link)) {
            $link = 'https://' . ltrim($link, '/');
        }

        return $link;
    }
}
