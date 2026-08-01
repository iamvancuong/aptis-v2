<?php

namespace App\Support;

/**
 * Rút đường dẫn file ghi âm từ cột `attempt_answers.answer`.
 *
 * Cột này không có hình dạng cố định: `MockTestController` ghi mảng phẳng,
 * `PracticeController` ghi mảng khác, model thì cast 'array' nên có lúc đọc ra
 * chuỗi JSON. Ba chỗ dùng (job chấm, dispatcher, lệnh dọn ổ đĩa) từng có ba
 * bản sao của cùng một vòng lặp — gom về đây để chúng không lệch nhau.
 */
class SpeakingAudio
{
    /** @return array<int, string> đường dẫn tương đối trên disk `public`, đã lọc trùng */
    public static function pathsOf(mixed $raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : [$raw];
        }

        if (!is_array($raw)) {
            return [];
        }

        $paths = [];
        array_walk_recursive($raw, function ($value) use (&$paths) {
            if (is_string($value) && str_contains($value, 'speaking_attempts/')) {
                $paths[] = $value;
            }
        });

        return array_values(array_unique($paths));
    }

    public static function hasRecording(mixed $raw): bool
    {
        return self::pathsOf($raw) !== [];
    }
}
