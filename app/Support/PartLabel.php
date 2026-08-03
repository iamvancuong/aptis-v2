<?php

namespace App\Support;

/**
 * Đổi số Part nội bộ sang số Part như trên đề thi APTIS thật.
 *
 * Đề Reading của APTIS có **5 phần**, còn hệ thống chỉ lưu 4: phần 2 ở đây gộp
 * nội dung mà đề thật tách thành phần 2 và phần 3. Học viên ôn theo đề thật nên
 * thấy "Part 3" ở đây mà "Part 4" trong sách là rối.
 *
 * ⚠️ CHỈ đổi NHÃN HIỂN THỊ. Số nội bộ (`quizzes.part`, `questions.part`, tham số
 * URL, dữ liệu chấm điểm) giữ nguyên 1–4 — đổi cả dữ liệu thì phải migrate mọi
 * bảng và mọi bộ đề đã nhập. Khu admin cũng giữ số nội bộ để việc nhập đề khớp
 * với cấu trúc thật của hệ thống.
 */
class PartLabel
{
    /** Số nội bộ => số trên đề thật. Kỹ năng nào không có ở đây thì giữ nguyên. */
    private const MAP = [
        'reading' => [1 => '1', 2 => '2-3', 3 => '4', 4 => '5'],
    ];

    /** Chỉ phần số, ví dụ "2-3". */
    public static function number(?string $skill, mixed $part): string
    {
        if (!is_numeric($part)) {
            return is_string($part) && $part !== '' ? $part : '?';
        }

        return self::MAP[$skill][(int) $part] ?? (string) (int) $part;
    }

    /** Nhãn đầy đủ, ví dụ "Part 2-3". */
    public static function text(?string $skill, mixed $part): string
    {
        return 'Part ' . self::number($skill, $part);
    }
}
