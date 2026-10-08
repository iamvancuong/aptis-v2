<?php

namespace App\Support;

/**
 * Quy đổi điểm % trên web sang thang điểm Aptis (0–50) và bậc CEFR.
 *
 * Bài thi Aptis thật chấm mỗi kỹ năng trên thang 0–50 rồi xếp bậc CEFR theo
 * ngưỡng riêng của từng kỹ năng. Web lưu điểm theo % (`attempts.score`) nên chỉ
 * quy đổi lúc HIỂN THỊ — không đổi DB, không đổi cách chấm.
 *
 * Ngưỡng nằm ở `config('aptis.cefr_bands')` — ngưỡng tham khảo phổ biến của
 * Aptis General (British Council có thể điều chỉnh theo từng kỳ), dùng để học
 * viên ước lượng trình độ.
 */
class AptisScale
{
    public const MAX = 50;

    /** Thứ tự bậc từ thấp lên cao — dùng cho biểu đồ và so sánh. */
    public const LEVELS = ['A0', 'A1', 'A2', 'B1', 'B2', 'C'];

    /** Các kỹ năng được xếp bậc CEFR (Grammar & Vocabulary thì không). */
    public const CEFR_SKILLS = ['listening', 'reading', 'speaking', 'writing'];

    /**
     * Ngưỡng Writing — giữ hằng số cho code cũ; nguồn thật là config.
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
     * Ngưỡng của một kỹ năng, xếp từ bậc cao xuống thấp.
     *
     * @return array<string, int>
     */
    public static function bands(string $skill): array
    {
        // Không có app (unit test thuần) thì dùng hằng số mặc định.
        $bands = function_exists('app') && app()->bound('config')
            ? config("aptis.cefr_bands.{$skill}")
            : null;

        if (! is_array($bands) || $bands === []) {
            $bands = $skill === 'writing' ? self::WRITING_BANDS : [];
        }

        arsort($bands);

        return $bands;
    }

    /**
     * Bậc CEFR theo điểm Aptis 0–50 của một kỹ năng.
     */
    public static function levelForScale(string $skill, int $scale): string
    {
        foreach (self::bands($skill) as $level => $min) {
            if ($scale >= $min) {
                return $level;
            }
        }

        return 'A0';
    }

    /**
     * Bậc CEFR theo % trên web của một kỹ năng.
     */
    public static function level(string $skill, ?float $percent): string
    {
        return self::levelForScale($skill, self::fromPercent($percent));
    }

    /**
     * Bậc tổng từ tổng điểm 4 kỹ năng (0–200): so với tổng ngưỡng từng bậc của
     * 4 kỹ năng (ví dụ B1 = 24 + 26 + 26 + 26 = 102).
     */
    public static function overallLevel(int $totalScale): string
    {
        $sums = [];

        foreach (self::CEFR_SKILLS as $skill) {
            foreach (self::bands($skill) as $level => $min) {
                $sums[$level] = ($sums[$level] ?? 0) + $min;
            }
        }

        arsort($sums);

        foreach ($sums as $level => $min) {
            if ($totalScale >= $min) {
                return $level;
            }
        }

        return 'A0';
    }

    /**
     * Tổng điểm 4 kỹ năng (0–200) tối thiểu để đạt bậc tổng `$level`.
     */
    public static function overallThreshold(string $level): int
    {
        $sum = 0;

        foreach (self::CEFR_SKILLS as $skill) {
            $sum += self::bands($skill)[$level] ?? 0;
        }

        return $sum;
    }

    /**
     * Chuẩn hoá mục tiêu của học viên về bậc Aptis: C1/C2 → C (Aptis không chia
     * C1/C2). Giá trị lạ → null.
     */
    public static function normalizeLevel(?string $level): ?string
    {
        $level = strtoupper(trim((string) $level));

        if (in_array($level, ['C1', 'C2'], true)) {
            return 'C';
        }

        return in_array($level, self::LEVELS, true) ? $level : null;
    }

    /** Vị trí của bậc trong LEVELS (A0 = 0 … C = 5). */
    public static function rank(string $level): int
    {
        $i = array_search($level, self::LEVELS, true);

        return $i === false ? 0 : $i;
    }

    /**
     * Bậc CEFR của bài Viết theo % trên web.
     */
    public static function writingLevel(?float $percent): string
    {
        return self::level('writing', $percent);
    }

    /**
     * Gói sẵn cho view: ['scale' => 30, 'level' => 'B1', 'color' => [...class]].
     */
    public static function writing(?float $percent): array
    {
        return self::forSkill('writing', $percent);
    }

    /**
     * Gói sẵn cho view của một kỹ năng bất kỳ.
     */
    public static function forSkill(string $skill, ?float $percent): array
    {
        $level = self::level($skill, $percent);

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
