<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VocabLookupUsage extends Model
{
    protected $fillable = [
        'user_id',
        'usage_date',
        'count',
        'api_calls',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
