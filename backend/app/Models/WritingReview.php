<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingReview extends Model
{
    protected $fillable = [
        'attempt_answer_id',
        'reviewer_id',
        'scores',
        'total_score',
        'comment',
    ];

    protected $casts = [
        'scores' => 'array',
        'total_score' => 'decimal:2',
    ];

    public function attemptAnswer(): BelongsTo
    {
        return $this->belongsTo(AttemptAnswer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
