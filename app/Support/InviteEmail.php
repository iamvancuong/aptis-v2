<?php

namespace App\Support;

/**
 * Kiểm tra chất lượng địa chỉ dùng để mời vào lớp Google Meet.
 *
 * Vì sao KHÔNG chỉ dùng regex cú pháp: quét dữ liệu thật (848 học viên) thấy
 * **0 email sai cú pháp** — regex bắt lỗi định dạng không cứu được gì. Cái thật
 * sự gây hại là **gõ nhầm tên miền Gmail**: `gmai.com`, `gmail.con`, `gamil.com`,
 * `gmail.com.vn`. Chúng hợp lệ về cú pháp nhưng KHÔNG TỒN TẠI, nên:
 *   - mời vào Calendar là mời vào hư không,
 *   - học viên đó không bao giờ vào thẳng được, buổi nào cũng phải xin duyệt,
 *   - giảng viên không hiểu vì sao.
 * Nên phải bắt bằng độ lệch ký tự so với `gmail.com`, không phải bằng regex.
 */
class InviteEmail
{
    /** Cú pháp email cơ bản — lưới đầu tiên, phòng dữ liệu xấu về sau. */
    private const PATTERN = '/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/';

    /** Địa chỉ có dùng để mời được không? */
    public static function isUsable(?string $email): bool
    {
        $email = trim((string) $email);

        if ($email === '' || ! preg_match(self::PATTERN, $email)) {
            return false;
        }

        return self::gmailTypoSuggestion($email) === null;
    }

    /**
     * Nếu tên miền trông như gõ nhầm `gmail.com`, trả về địa chỉ đã sửa để gợi ý.
     * Không phải gõ nhầm thì trả null.
     */
    public static function gmailTypoSuggestion(?string $email): ?string
    {
        $email = trim((string) $email);
        $at = strrpos($email, '@');

        if ($at === false) {
            return null;
        }

        $local  = substr($email, 0, $at);
        $domain = strtolower(substr($email, $at + 1));

        if ($domain === 'gmail.com' || $domain === '') {
            return null;
        }

        // "gmail.com.vn", "gmail.vn"… — bắt đầu bằng "gmail." nhưng không phải Gmail thật.
        $bienTheGmail = str_starts_with($domain, 'gmail.');

        // Lệch 1–2 ký tự so với "gmail.com" gần như chắc chắn là gõ nhầm.
        // Không nới lên 3: dễ bắt oan tên miền hợp lệ khác.
        $gonGiong = levenshtein($domain, 'gmail.com') <= 2;

        return ($bienTheGmail || $gonGiong) ? $local . '@gmail.com' : null;
    }
}
