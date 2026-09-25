<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Thư mục từ vựng do học viên tự tạo. Mỗi từ nằm tối đa trong một thư mục.
 */
class VocabFolder extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'sort_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(VocabularyItem::class, 'folder_id');
    }
}
