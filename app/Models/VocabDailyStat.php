<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Lượt ôn từ vựng theo ngày — dùng để tính chuỗi ngày học (streak).
 */
class VocabDailyStat extends Model
{
    protected $fillable = [
        'user_id',
        'stat_date',
        'reviews',
        'correct',
    ];

    protected function casts(): array
    {
        return [
            'stat_date' => 'date',
            'reviews' => 'integer',
            'correct' => 'integer',
        ];
    }

    /** Ghi nhận một lượt ôn của hôm nay. */
    public static function record(int $userId, bool $correct): void
    {
        // Truyền Carbon chứ KHÔNG truyền chuỗi 'Y-m-d' — cùng bẫy với
        // VocabLookupUsage: cột cast `date` lưu '… 00:00:00', tìm bằng chuỗi
        // ngày trần sẽ không khớp và insert trùng.
        $row = static::firstOrCreate(
            ['user_id' => $userId, 'stat_date' => today()],
            ['reviews' => 0, 'correct' => 0],
        );

        // Cộng trong SQL: hai tab ôn song song không nuốt mất lượt của nhau.
        DB::table('vocab_daily_stats')->where('id', $row->id)->update([
            'reviews' => DB::raw('reviews + 1'),
            'correct' => DB::raw('correct + ' . ($correct ? 1 : 0)),
            'updated_at' => now(),
        ]);
    }

    /**
     * Số ngày liên tiếp có ôn, tính tới hôm nay. Hôm nay chưa ôn thì chuỗi vẫn
     * còn nếu hôm qua có ôn — học viên còn cả ngày để giữ chuỗi.
     */
    public static function streak(int $userId): int
    {
        $dates = static::where('user_id', $userId)
            ->where('reviews', '>', 0)
            ->where('stat_date', '>=', today()->subDays(400))
            ->orderByDesc('stat_date')
            ->pluck('stat_date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        if ($dates === []) {
            return 0;
        }

        $cursor = today();
        if ($dates[0] !== $cursor->toDateString()) {
            $cursor = $cursor->subDay();
            if ($dates[0] !== $cursor->toDateString()) {
                return 0;
            }
        }

        $streak = 0;
        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    public static function reviewedToday(int $userId): int
    {
        return (int) static::where('user_id', $userId)
            ->whereDate('stat_date', today())
            ->value('reviews');
    }
}
