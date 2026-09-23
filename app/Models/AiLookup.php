<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Một mục trong kho đệm tra từ dùng chung cho toàn hệ thống.
 */
class AiLookup extends Model
{
    protected $fillable = [
        'hash',
        'term',
        'mode',
        'payload',
        'hit_count',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Khoá đệm. Chuẩn hoá trước khi băm để "However" và "however " trúng cùng
     * một dòng.
     */
    public static function makeHash(string $mode, string $normalizedTerm): string
    {
        return hash('sha256', $mode . '|' . $normalizedTerm);
    }
}
