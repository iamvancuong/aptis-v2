<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'quiz_id',
        'skill',
        'part',
        'type',
        'title',
        'stem',
        'audio_path',
        'image_path',
        'point',
        'order',
        'metadata',
        'explanation',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function sets(): BelongsToMany
    {
        return $this->belongsToMany(Set::class, 'set_question');
    }

    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
