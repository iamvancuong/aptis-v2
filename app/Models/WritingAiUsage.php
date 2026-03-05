<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WritingAiUsage extends Model
{
    protected $fillable = [
        'user_id',
        'writing_part',
        'usage_count',
        'reset_version',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
