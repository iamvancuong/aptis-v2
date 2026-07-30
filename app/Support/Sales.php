<?php

namespace App\Support;

/**
 * Truy vấn danh sách sale giới thiệu (định nghĩa cứng ở config/sales.php).
 * Mã sale được chuẩn hoá về CHỮ HOA để so khớp không phân biệt hoa/thường.
 */
class Sales
{
    /**
     * Chuẩn hoá mã sale từ input người dùng (URL/form). Trả '' nếu rỗng.
     */
    public static function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    /**
     * Mã sale hợp lệ VÀ đang bật? (chỉ mã active mới được gắn cho đơn mới).
     */
    public static function isValid(?string $code): bool
    {
        $rep = config('sales.reps.' . self::normalize($code));

        return is_array($rep) && ($rep['active'] ?? false) === true;
    }

    /**
     * Trả mã đã chuẩn hoá nếu hợp lệ+active, ngược lại null.
     */
    public static function resolve(?string $code): ?string
    {
        return self::isValid($code) ? self::normalize($code) : null;
    }

    /**
     * Tên hiển thị của sale; fallback về chính mã nếu không tìm thấy (đơn cũ có
     * mã sale nhưng sau này sale bị xoá khỏi config vẫn hiện được).
     */
    public static function name(?string $code): string
    {
        $code = self::normalize($code);

        return config('sales.reps.' . $code . '.name', $code);
    }

    /**
     * Danh sách sale đang bật: ['M1' => ['name' => ...], ...].
     */
    public static function active(): array
    {
        return array_filter(
            config('sales.reps', []),
            fn ($rep) => ($rep['active'] ?? false) === true,
        );
    }
}
