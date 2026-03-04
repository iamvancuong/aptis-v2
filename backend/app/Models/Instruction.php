<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instruction extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'content',
        'video_path',
        'video_url',
        'is_published',
        'sort_order',
    ];
    
    // Auto-generate slug from title
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($instruction) {
            if (empty($instruction->slug)) {
                $instruction->slug = \Illuminate\Support\Str::slug($instruction->title);
            }
        });
    }
}
