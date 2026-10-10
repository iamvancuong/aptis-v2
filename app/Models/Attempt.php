<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    protected $fillable = [
        'user_id',
        'skill',
        'mode',
        'set_id',
        'mock_test_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'score',
        'metadata',
        'is_grading_requested',
        'grading_requested_at',
        'is_seen',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'score' => 'decimal:2',
        'metadata' => 'array',
        'is_grading_requested' => 'boolean',
        'is_seen' => 'boolean',
        'grading_requested_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class);
    }

    public function mockTest(): BelongsTo
    {
        return $this->belongsTo(MockTest::class);
    }

    /**
     * Điều kiện "phần này còn chờ GIÁO VIÊN chấm" — một nguồn cho trang chấm
     * Writing/Speaking và số đếm trên sidebar admin (trước đây mỗi trang tự viết,
     * dễ lệch nhau).
     *   Writing : mọi trạng thái trừ 'graded' (gồm cả limit_reached / ai_graded / null).
     *   Speaking: mọi trạng thái trừ 'graded' / 'manually_graded'.
     */
    public static function notGradedByTeacher(string $skill): \Closure
    {
        return $skill === 'writing'
            ? fn ($q) => $q->where(fn ($w) => $w->where('grading_status', '!=', 'graded')->orWhereNull('grading_status'))
            : fn ($q) => $q->whereNotIn('grading_status', ['graded', 'manually_graded']);
    }

    /** Bài thi thử đã gửi giáo viên chấm (hàng đợi chấm tay) của một kỹ năng. */
    public function scopeTeacherQueue($query, string $skill)
    {
        return $query->where('skill', $skill)
            ->whereIn('mode', ['mock', 'mock_test'])
            ->where('is_grading_requested', true);
    }

    /** Hàng đợi chấm tay, chỉ những bài còn phần chưa chấm. */
    public function scopeAwaitingTeacher($query, string $skill)
    {
        return $query->teacherQueue($skill)->whereHas('attemptAnswers', self::notGradedByTeacher($skill));
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
