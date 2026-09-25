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
     * Phiên bản prompt. Tăng số này mỗi khi prompt/schema đổi theo cách làm kết
     * quả cũ sai: mọi dòng đệm cũ tự bị bỏ qua mà không phải xoá bảng.
     * v2: dịch nguyên cụm từ 2 từ trở lên + thêm `word_type`.
     */
    public const PROMPT_VERSION = 'v2';

    /**
     * Khoá đệm. Chuẩn hoá trước khi băm để "However" và "however " trúng cùng
     * một dòng.
     */
    public static function makeHash(string $mode, string $normalizedTerm): string
    {
        return hash('sha256', self::PROMPT_VERSION . '|' . $mode . '|' . $normalizedTerm);
    }
}
