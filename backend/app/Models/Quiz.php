<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'skill',
        'part',
        'duration_minutes',
        'is_published',
        'metadata',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'metadata' => 'array',
    ];

    public function sets(): HasMany
    {
        return $this->hasMany(Set::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
