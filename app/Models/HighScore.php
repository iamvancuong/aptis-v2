<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HighScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'avatar',
        'certificate',
        'is_active',
    ];
}
