<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidanceBooking extends Model
{
    protected $fillable = [
        'user_id',
        'session_date',
        'zoom_link',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
