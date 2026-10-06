<?php

namespace App\Support;

/**
 * Quy đổi điểm % trên web sang thang điểm Aptis (0–50) và bậc CEFR.
 *
 * Bài thi Aptis thật chấm mỗi kỹ năng trên thang 0–50 rồi xếp bậc CEFR theo
 * ngưỡng riêng của từng kỹ năng. Web lưu điểm theo % (`attempts.score`) nên chỉ
 * quy đổi lúc HIỂN THỊ — không đổi DB, không đổi cách chấm, chỉnh ngưỡng ở đây
 * là toàn site đổi theo.
 *
 * Ngưỡng Writing là ngưỡng tham khảo phổ biến của Aptis General (British
 * Council có thể điều chỉnh theo từng kỳ), dùng để học viên ước lượng trình độ.
 */
class AptisScale
{
    public const MAX = 50;

    /**
     * Điểm Aptis tối thiểu để đạt từng bậc — xếp từ cao xuống thấp.
     */
    public const WRITING_BANDS = [
        'C'  => 48,
        'B2' => 40,
        'B1' => 26,
        'A2' => 18,
        'A1' => 6,
        'A0' => 0,
    ];

    /**
     * % → điểm Aptis 0–50 (số nguyên, như phiếu điểm thật).
     */
    public static function fromPercent(?float $percent): int
    {
        $percent = max(0, min(100, (float) $percent));

        return (int) round($percent * self::MAX / 100);
    }

    /**
     * Bậc CEFR của bài Viết theo % trên web.
     */
    public static function writingLevel(?float $percent): string
    {
        $scale = self::fromPercent($percent);

        foreach (self::WRITING_BANDS as $level => $min) {
            if ($scale >= $min) {
                return $level;
            }
        }

        return 'A0';
    }

    /**
     * Gói sẵn cho view: ['scale' => 30, 'level' => 'B1', 'color' => [...class]].
     */
    public static function writing(?float $percent): array
    {
        $level = self::writingLevel($percent);

        return [
            'scale' => self::fromPercent($percent),
            'level' => $level,
            'color' => self::color($level),
        ];
    }

    /**
     * Class Tailwind theo bậc: C/B2 xanh lá, B1 xanh dương, A2 vàng, A1/A0 đỏ.
     * Viết nguyên văn từng class để Tailwind quét thấy.
     */
    public static function color(string $level): array
    {
        return match ($level) {
            'C', 'B2' => ['text' => 'text-green-600', 'bg' => 'from-green-50 to-emerald-50', 'badge' => 'bg-green-100 text-green-800'],
            'B1'      => ['text' => 'text-blue-600',  'bg' => 'from-blue-50 to-indigo-50',   'badge' => 'bg-blue-100 text-blue-800'],
            'A2'      => ['text' => 'text-amber-600', 'bg' => 'from-amber-50 to-yellow-50',  'badge' => 'bg-amber-100 text-amber-800'],
            default   => ['text' => 'text-red-600',   'bg' => 'from-red-50 to-orange-50',    'badge' => 'bg-red-100 text-red-800'],
        };
    }
}
