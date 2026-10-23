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
     * Bậc tổng = trung bình cộng bậc của 4 kỹ năng, .5 làm tròn lên (cách của
     * British Council): B2+B2+C+C = C, B1+B1+B2+B2 = B2, C+B2+A2+B1 = B2.
     * Kỹ năng thiếu (null) tính là A0.
     *
     * Không so tổng điểm /200: ngưỡng từng kỹ năng khác nhau nên B1+B1+B2+B2
     * có thể chỉ được ~140 điểm < tổng ngưỡng B2 (153) mà vẫn là B2.
     *
     * @param  array<string, ?string>  $levels  skill => bậc
     */
    public static function overallLevel(array $levels): string
    {
        $sum = 0;
        foreach (self::CEFR_SKILLS as $skill) {
            $sum += self::rank((string) ($levels[$skill] ?? 'A0'));
        }

        // Tổng bậc ≥ 4·r − 2 ⇔ trung bình ≥ r − 0.5. Số nguyên nên không lệch.
        $rank = intdiv($sum + 2, count(self::CEFR_SKILLS));

        return self::LEVELS[min($rank, count(self::LEVELS) - 1)];
    }

    /**
     * Ít điểm Aptis nhất phải cộng thêm (tổng các kỹ năng) để bậc tổng đạt
     * `$target` — thử mọi cách nâng bậc từng kỹ năng (6^4 tổ hợp, rất nhẹ).
     *
     * @param  array<string, int>  $scales  skill => điểm 0–50
     */
    public static function pointsToReach(array $scales, string $target): int
    {
        $options = [];
        foreach (self::CEFR_SKILLS as $skill) {
            $scale = (int) ($scales[$skill] ?? 0);
            $bands = self::bands($skill);
            $current = self::rank(self::levelForScale($skill, $scale));

            // [số bậc tăng, điểm cần thêm]
            $options[$skill] = [[0, 0]];
            for ($r = $current + 1; $r < count(self::LEVELS); $r++) {
                $min = $bands[self::LEVELS[$r]] ?? null;
                if ($min !== null) {
                    $options[$skill][] = [$r - $current, max(0, $min - $scale)];
                }
            }
        }

        $levels = [];
        foreach (self::CEFR_SKILLS as $skill) {
            $levels[$skill] = self::levelForScale($skill, (int) ($scales[$skill] ?? 0));
        }
        $currentSum = array_sum(array_map(fn ($l) => self::rank($l), $levels));
        $need = max(0, 4 * self::rank($target) - 2 - $currentSum);

        $best = PHP_INT_MAX;
        $walk = function (array $skills, int $gain, int $cost) use (&$walk, &$best, $options, $need) {
            if ($cost >= $best) {
                return;
            }
            if ($gain >= $need) {
                $best = $cost;

                return;
            }
            if ($skills === []) {
                return;
            }
            $skill = array_shift($skills);
            foreach ($options[$skill] as [$g, $c]) {
                $walk($skills, $gain + $g, $cost + $c);
            }
        };
        $walk(self::CEFR_SKILLS, 0, 0);

        return $best === PHP_INT_MAX ? 0 : $best;
    }

    /**
     * Điểm % của một kỹ năng theo tỉ trọng part của bài thi thật
     * (`config('aptis.part_weights')`): Σ(tỉ lệ đúng part × điểm tối đa part)
     * chia cho tổng điểm tối đa các part có trong đề. Kỹ năng không có tỉ trọng
     * → trung bình các part.
     *
     * @param  array<int, float>  $partRatios  part => tỉ lệ đúng 0–1
     */
    public static function weightedPercent(string $skill, array $partRatios): float
    {
        if ($partRatios === []) {
            return 0.0;
        }

        $weights = function_exists('app') && app()->bound('config')
            ? (array) config("aptis.part_weights.{$skill}", [])
            : [];

        $earned = 0.0;
        $max = 0.0;
        foreach ($partRatios as $part => $ratio) {
            $w = (float) ($weights[$part] ?? ($weights === [] ? 1 : 0));
            $earned += $w * max(0.0, min(1.0, $ratio));
            $max += $w;
        }

        return $max > 0 ? round($earned / $max * 100, 2) : 0.0;
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
