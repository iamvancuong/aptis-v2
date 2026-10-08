<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Một lượt Full Test: 5 phần thi liên tục theo `config('aptis.full_test.stages')`.
 * Mỗi phần là một MockTest gắn `full_test_id`; `current_stage` là chỉ số phần
 * đang làm (0..4). Xem App\Services\FullTestService.
 */
class FullTest extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'current_stage',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'current_stage' => 'integer',
        'started_at'    => 'datetime',
        'finished_at'   => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mockTests(): HasMany
    {
        return $this->hasMany(MockTest::class);
    }

    /** @return array<int, string> */
    public static function stages(): array
    {
        return config('aptis.full_test.stages', ['speaking', 'listening', 'grammar', 'reading', 'writing']);
    }

    public function currentSkill(): ?string
    {
        return self::stages()[$this->current_stage] ?? null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /** Bài thi của một kỹ năng trong lượt này (null nếu chưa tới phần đó). */
    public function stageMock(string $skill): ?MockTest
    {
        return $this->mockTests->firstWhere('skill', $skill);
    }

    /** Mã hiển thị trên bảng điểm, ví dụ "FT-000123". */
    public function code(): string
    {
        return 'FT-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
