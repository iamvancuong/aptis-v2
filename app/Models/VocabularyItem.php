<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class VocabularyItem extends Model
{
    /**
     * Lịch ôn theo hộp Leitner (số ngày tới lần ôn kế tiếp).
     *
     * Chọn Leitner thay vì SM-2: học viên chỉ bấm Quên / Mơ hồ / Nhớ, không phải
     * tự chấm 0–5 như SM-2 đòi hỏi. Thực tế người học không chấm nổi chính xác
     * thang đó, nên SM-2 cũng không cho lịch tốt hơn mà giao diện lại rối hơn.
     */
    public const BOX_INTERVALS = [
        1 => 1,
        2 => 3,
        3 => 7,
        4 => 16,
        5 => 35,
    ];

    public const MAX_BOX = 5;

    protected $fillable = [
        'user_id',
        'term',
        'normalized_term',
        'meaning',
        'part_of_speech',
        'phonetic',
        'example',
        'cefr',
        'context_sentence',
        'source_skill',
        'source_part',
        'source_set_id',
        'box',
        'interval_days',
        'due_at',
        'last_reviewed_at',
        'reviews_count',
        'correct_count',
        'streak',
    ];

    /**
     * Giá trị mặc định phải khai ở ĐÂY chứ không chỉ ở migration: một model vừa
     * create() xong không đọc lại default của DB, nên `box` sẽ là null và phép
     * tính lịch ôn ngay sau đó ra sai.
     */
    protected $attributes = [
        'box' => 1,
        'interval_days' => 0,
        'reviews_count' => 0,
        'correct_count' => 0,
        'streak' => 0,
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'box' => 'integer',
            'interval_days' => 'integer',
            'reviews_count' => 'integer',
            'correct_count' => 'integer',
            'streak' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Chuẩn hoá từ để so trùng: bỏ khoảng trắng thừa, hạ chữ thường, bỏ dấu câu
     * bám hai đầu. Giữ nguyên dấu nháy bên trong ("don't" ≠ "dont").
     */
    public static function normalize(string $term): string
    {
        $term = preg_replace('/\s+/u', ' ', trim($term)) ?? $term;
        $term = trim($term, " \t\n\r\0\x0B.,;:!?\"()[]{}");

        return mb_strtolower($term);
    }

    /** Từ tới hạn ôn (kể cả từ mới chưa ôn lần nào). */
    public function scopeDue(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();

        return $query->where(function (Builder $q) use ($now) {
            $q->whereNull('due_at')->orWhere('due_at', '<=', $now);
        });
    }

    /**
     * "Đã thuộc" = lên tới hộp cuối và còn nhớ đúng thêm một lần nữa ở đó.
     * Chỉ dựa vào box thì một từ đoán mò đúng 5 lần cũng bị coi là thuộc.
     */
    public function isMastered(): bool
    {
        return $this->box >= self::MAX_BOX && $this->streak >= 2;
    }

    /**
     * Ghi nhận một lượt ôn và dời lịch.
     *
     * @param  string  $result  again | hard | good
     */
    public function recordReview(string $result): void
    {
        $this->reviews_count++;

        if ($result === 'again') {
            // Quên thì trả hẳn về hộp 1 — nửa vời (lùi 1 hộp) khiến từ khó cứ
            // luẩn quẩn ở hộp giữa và không bao giờ được ôn đủ dày.
            $this->box = 1;
            $this->streak = 0;
        } elseif ($result === 'hard') {
            // Giữ nguyên hộp: nhớ được nhưng chưa chắc, ôn lại đúng nhịp cũ.
            $this->streak = 0;
        } else {
            $this->box = min(self::MAX_BOX, $this->box + 1);
            $this->streak++;
            $this->correct_count++;
        }

        $this->interval_days = self::BOX_INTERVALS[$this->box] ?? 1;
        // Mốc là đầu ngày để từ đến hạn hiện ra ngay từ sáng, thay vì phải chờ
        // đúng giờ phút của lần ôn trước.
        $this->due_at = now()->startOfDay()->addDays($this->interval_days);
        $this->last_reviewed_at = now();

        $this->save();
    }
}
