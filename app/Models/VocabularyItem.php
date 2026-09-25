<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class VocabularyItem extends Model
{
    /**
     * Thư mục tự động theo loại từ. Khoá là mã lưu trong cột `word_type` (AI
     * trả về), giá trị là nhãn hiển thị.
     */
    public const WORD_TYPES = [
        'noun' => 'Danh từ',
        'verb' => 'Động từ',
        'adjective' => 'Tính từ',
        'adverb' => 'Trạng từ',
        'phrase' => 'Cụm từ / Thành ngữ',
        'sentence' => 'Câu',
        'other' => 'Khác',
    ];

    /** Kết quả một lượt ôn: Quên / Mơ hồ / Nhớ. */
    public const RESULTS = ['again', 'hard', 'good'];

    protected $fillable = [
        'user_id',
        'folder_id',
        'term',
        'normalized_term',
        'meaning',
        'word_type',
        'part_of_speech',
        'phonetic',
        'example',
        'cefr',
        'context_sentence',
        'source_skill',
        'source_part',
        'source_set_id',
        'srs_state',
        'step',
        'interval_days',
        'ease',
        'lapses',
        'due_at',
        'last_reviewed_at',
        'reviews_count',
        'correct_count',
    ];

    /**
     * Giá trị mặc định phải khai ở ĐÂY chứ không chỉ ở migration: một model vừa
     * create() xong không đọc lại default của DB, nên `srs_state` sẽ là null và
     * phép tính lịch ôn ngay sau đó ra sai.
     */
    protected $attributes = [
        'srs_state' => 'new',
        'step' => 0,
        'interval_days' => 0,
        'ease' => 2500,
        'lapses' => 0,
        'reviews_count' => 0,
        'correct_count' => 0,
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_reviewed_at' => 'datetime',
            'folder_id' => 'integer',
            'step' => 'integer',
            'interval_days' => 'integer',
            'ease' => 'integer',
            'lapses' => 'integer',
            'reviews_count' => 'integer',
            'correct_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(VocabFolder::class, 'folder_id');
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

    /**
     * Đoán mã loại từ từ nhãn tiếng Việt — dự phòng khi AI không trả
     * `word_type`, hoặc từ được lưu từ dữ liệu cũ.
     */
    public static function guessWordType(?string $partOfSpeech): string
    {
        $pos = mb_strtolower(trim((string) $partOfSpeech));

        return match (true) {
            $pos === '' => 'other',
            // "cụm …" / thành ngữ phải xét TRƯỚC "động từ", "danh từ": "cụm động
            // từ" chứa chữ "động từ" nhưng là một cụm.
            str_contains($pos, 'cụm') || str_contains($pos, 'thành ngữ') => 'phrase',
            str_contains($pos, 'câu') || str_contains($pos, 'mệnh đề') => 'sentence',
            str_contains($pos, 'danh từ') => 'noun',
            str_contains($pos, 'động từ') => 'verb',
            str_contains($pos, 'tính từ') => 'adjective',
            str_contains($pos, 'trạng từ') || str_contains($pos, 'phó từ') => 'adverb',
            default => 'other',
        };
    }

    public function wordTypeLabel(): string
    {
        return self::WORD_TYPES[$this->word_type ?? 'other'] ?? self::WORD_TYPES['other'];
    }

    /* ─────────────────────────── Truy vấn ─────────────────────────── */

    /** Từ tới hạn ôn (kể cả từ mới chưa ôn lần nào). */
    public function scopeDue(Builder $query, ?Carbon $now = null): Builder
    {
        $now ??= now();

        return $query->where(function (Builder $q) use ($now) {
            $q->whereNull('due_at')->orWhere('due_at', '<=', $now);
        });
    }

    public function scopeMastered(Builder $query): Builder
    {
        return $query->where('srs_state', 'review')
            ->where('interval_days', '>=', self::masteredInterval());
    }

    public function scopeLearning(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('srs_state', '!=', 'review')
                ->orWhere('interval_days', '<', self::masteredInterval());
        });
    }

    /* ─────────────────────── Lịch ôn kiểu Anki ─────────────────────── */

    protected static function srs(string $key): mixed
    {
        return config('aptis.vocab.srs.' . $key);
    }

    protected static function masteredInterval(): int
    {
        return (int) self::srs('mastered_interval');
    }

    /** "Đã thuộc" = đã tốt nghiệp và khoảng cách ôn đủ dài (Anki: thẻ mature). */
    public function isMastered(): bool
    {
        return $this->srs_state === 'review' && $this->interval_days >= self::masteredInterval();
    }

    public function isLearning(): bool
    {
        return in_array($this->srs_state, ['new', 'learning', 'relearning'], true);
    }

    /**
     * Tính trạng thái mới sau một lượt ôn, KHÔNG lưu. Tách riêng để giao diện
     * hiện trước được "Quên · 1 phút / Nhớ · 10 phút" trên từng nút.
     *
     * Giai đoạn học (thẻ mới hoặc vừa quên) đi qua các bước tính bằng phút:
     *   Nhớ → bước kế; qua bước cuối thì tốt nghiệp sang ôn theo ngày.
     *   Mơ hồ → lặp lại bước hiện tại.
     *   Quên → về bước đầu.
     * Giai đoạn ôn theo ngày:
     *   Nhớ → khoảng cách × ease.  Mơ hồ → × 1.2, ease giảm.
     *   Quên → reset về bước học đầu tiên, ease giảm, tính một lần "quên".
     *
     * @return array{srs_state: string, step: int, interval_days: int, ease: int, lapses: int, due_at: Carbon}
     */
    public function schedule(string $result, ?Carbon $now = null): array
    {
        $now = ($now ?? now())->copy();
        $steps = array_values((array) self::srs('learning_steps')) ?: [1];
        $minEase = (int) self::srs('min_ease');
        $maxInterval = (int) self::srs('max_interval');

        $state = $this->srs_state ?: 'new';
        $step = (int) $this->step;
        $interval = (int) $this->interval_days;
        $ease = (int) ($this->ease ?: self::srs('starting_ease'));
        $lapses = (int) $this->lapses;

        if ($state === 'review') {
            if ($result === 'again') {
                $lapses++;
                $ease = max($minEase, $ease - 200);

                return $this->learningStep('relearning', 0, $steps, $interval, $ease, $lapses, $now);
            }

            $interval = $result === 'hard'
                ? max($interval + 1, (int) round($interval * (float) self::srs('hard_multiplier')))
                : max($interval + 1, (int) round($interval * $ease / 1000));

            if ($result === 'hard') {
                $ease = max($minEase, $ease - 150);
            }

            return $this->graduated(min($maxInterval, $interval), $ease, $lapses, $now);
        }

        // new | learning | relearning
        if ($state === 'new') {
            $state = 'learning';
            $step = 0;
        }
        $step = min($step, count($steps) - 1);

        if ($result === 'again') {
            return $this->learningStep($state, 0, $steps, $interval, $ease, $lapses, $now);
        }

        if ($result === 'hard') {
            return $this->learningStep($state, $step, $steps, $interval, $ease, $lapses, $now);
        }

        if ($step + 1 < count($steps)) {
            return $this->learningStep($state, $step + 1, $steps, $interval, $ease, $lapses, $now);
        }

        // Qua bước cuối → tốt nghiệp. Thẻ học lại sau khi quên cũng bắt đầu lại
        // từ khoảng cách tốt nghiệp: "quên thì reset" đúng như Anki.
        return $this->graduated((int) self::srs('graduating_interval'), $ease, $lapses, $now);
    }

    protected function learningStep(string $state, int $step, array $steps, int $interval, int $ease, int $lapses, Carbon $now): array
    {
        return [
            'srs_state' => $state,
            'step' => $step,
            'interval_days' => $interval,
            'ease' => $ease,
            'lapses' => $lapses,
            'due_at' => $now->copy()->addMinutes((int) $steps[$step]),
        ];
    }

    protected function graduated(int $interval, int $ease, int $lapses, Carbon $now): array
    {
        return [
            'srs_state' => 'review',
            'step' => 0,
            'interval_days' => max(1, $interval),
            'ease' => $ease,
            'lapses' => $lapses,
            // Ôn theo ngày thì mốc là đầu ngày, để từ đến hạn hiện ra ngay từ
            // sáng thay vì phải chờ đúng giờ phút của lần ôn trước.
            'due_at' => $now->copy()->startOfDay()->addDays(max(1, $interval)),
        ];
    }

    /**
     * Ghi nhận một lượt ôn và dời lịch.
     *
     * @param  string  $result  again | hard | good
     */
    public function recordReview(string $result): void
    {
        $this->fill($this->schedule($result));

        $this->reviews_count++;
        if ($result === 'good') {
            $this->correct_count++;
        }
        $this->last_reviewed_at = now();

        $this->save();
    }

    /**
     * Nhãn khoảng chờ cho từng nút: ['again' => '1 phút', 'hard' => '5 phút',
     * 'good' => '10 phút'].
     */
    public function previewIntervals(?Carbon $now = null): array
    {
        $now ??= now();
        $labels = [];

        foreach (self::RESULTS as $result) {
            $next = $this->schedule($result, $now);
            $labels[$result] = $next['srs_state'] === 'review'
                ? self::humanizeDays($next['interval_days'])
                : self::humanizeMinutes((int) round($now->diffInMinutes($next['due_at'], true)));
        }

        return $labels;
    }

    public static function humanizeMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return max(1, $minutes) . ' phút';
        }

        if ($minutes < 1440) {
            return round($minutes / 60) . ' giờ';
        }

        return self::humanizeDays((int) round($minutes / 1440));
    }

    public static function humanizeDays(int $days): string
    {
        if ($days < 30) {
            return $days . ' ngày';
        }

        if ($days < 365) {
            return str_replace('.', ',', (string) round($days / 30, 1)) . ' tháng';
        }

        return str_replace('.', ',', (string) round($days / 365, 1)) . ' năm';
    }

    /** Một dòng mô tả tiến độ cho thẻ trong sổ tay. */
    public function statusLabel(): string
    {
        $steps = count((array) self::srs('learning_steps'));

        return match ($this->srs_state) {
            'new' => 'Từ mới',
            'learning' => 'Đang học · bước ' . ($this->step + 1) . '/' . $steps,
            'relearning' => 'Học lại · bước ' . ($this->step + 1) . '/' . $steps,
            default => $this->isMastered() ? 'Đã thuộc' : 'Ôn mỗi ' . self::humanizeDays($this->interval_days),
        };
    }
}
