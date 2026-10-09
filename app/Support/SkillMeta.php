<?php

namespace App\Support;

/**
 * Thông tin hiển thị của 5 kỹ năng (tên, mô tả, màu, icon) — MỘT nguồn duy nhất
 * cho dashboard, trang kỹ năng, danh sách đề… Trước đây mỗi view tự khai báo
 * màu/icon riêng nên lệch nhau và phình code.
 *
 * `tone` là class Tailwind viết NGUYÊN VĂN (không ghép chuỗi) để Tailwind quét
 * thấy khi build.
 */
class SkillMeta
{
    /** Thứ tự theo đề thi Aptis. */
    public const ORDER = ['speaking', 'listening', 'grammar', 'reading', 'writing'];

    private const META = [
        'speaking'  => ['name' => 'Speaking',  'desc' => 'Kỹ năng nói',        'icon' => 'mic',      'color' => '#f97316', 'tone' => 'bg-orange-50 text-orange-600'],
        'listening' => ['name' => 'Listening', 'desc' => 'Kỹ năng nghe',       'icon' => 'volume',   'color' => '#10b981', 'tone' => 'bg-emerald-50 text-emerald-600'],
        'grammar'   => ['name' => 'Grammar',   'desc' => 'Ngữ pháp & Từ vựng', 'icon' => 'book',     'color' => '#6366f1', 'tone' => 'bg-indigo-50 text-indigo-600'],
        'reading'   => ['name' => 'Reading',   'desc' => 'Kỹ năng đọc hiểu',   'icon' => 'document', 'color' => '#0ea5e9', 'tone' => 'bg-sky-50 text-sky-600'],
        'writing'   => ['name' => 'Writing',   'desc' => 'Kỹ năng viết',       'icon' => 'pencil',   'color' => '#ec4899', 'tone' => 'bg-pink-50 text-pink-600'],
    ];

    /** @return array{name: string, desc: string, icon: string, color: string, tone: string} */
    public static function get(string $skill): array
    {
        return self::META[$skill] ?? ['name' => ucfirst($skill), 'desc' => '', 'icon' => 'book', 'color' => '#64748b', 'tone' => 'bg-gray-100 text-gray-600'];
    }

    /** Màu đường biểu đồ theo kỹ năng (cho JS). */
    public static function colors(): array
    {
        return array_map(fn ($m) => $m['color'], self::META);
    }

    /** Writing/Speaking chỉ luyện dạng thi thử toàn bộ kỹ năng. */
    public static function mockOnly(string $skill): bool
    {
        return in_array($skill, ['writing', 'speaking'], true);
    }
}
