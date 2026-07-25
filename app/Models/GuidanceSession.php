<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuidanceSession extends Model
{
    protected $fillable = [
        'session_date',
        'zoom_meeting_id',
        'join_url',
        'start_url',
        'passcode',
        'sent_at',
    ];

    protected $casts = [
        'session_date' => 'date',
        'sent_at'      => 'datetime',
    ];

    public function hasRoom(): bool
    {
        return filled($this->join_url);
    }
}
