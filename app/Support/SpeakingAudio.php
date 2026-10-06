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

    /**
     * Lưu một file ghi âm học viên tải lên; trả về đường dẫn trên disk `public`,
     * hoặc null nếu file không phải audio hợp lệ (bỏ qua, không lưu).
     *
     * BẢO MẬT: bản cũ gọi thẳng `$file->store()` — Laravel tự chọn đuôi file theo
     * NỘI DUNG, nên một "bản ghi âm" chứa HTML được lưu thành `.html`, SVG thành
     * `.svg`, và được phục vụ công khai ở `/storage/...` ngay trên milaedu.com
     * (stored XSS — gửi link cho admin là chiếm phiên admin). Giờ:
     *   - tự đọc chữ ký nhị phân (magic bytes) để nhận diện, KHÔNG tin tên file,
     *     Content-Type trình duyệt gửi hay libmagic của server;
     *   - đuôi file do server quyết định từ danh sách trắng định dạng audio;
     *   - chặn kích thước (giới hạn của API chuyển giọng nói).
     */
    public static function storeUpload(mixed $file): ?string
    {
        if (! $file instanceof \Illuminate\Http\UploadedFile || ! $file->isValid()) {
            return null;
        }

        $size = (int) $file->getSize();
        if ($size <= 0 || $size > \App\Services\AiService::MAX_AUDIO_BYTES) {
            \Illuminate\Support\Facades\Log::warning('Speaking upload bị từ chối: kích thước không hợp lệ', ['size' => $size]);
            return null;
        }

        $ext = self::sniffAudioExtension((string) file_get_contents($file->getRealPath(), false, null, 0, 64));
        if ($ext === null) {
            \Illuminate\Support\Facades\Log::warning('Speaking upload bị từ chối: không phải file audio', [
                'client_name' => $file->getClientOriginalName(),
                'user_id'     => auth()->id(),
            ]);
            return null;
        }

        $path = $file->storeAs('speaking_attempts', \Illuminate\Support\Str::random(40) . '.' . $ext, 'public');

        return $path ?: null;
    }

    /**
     * Nhận diện định dạng audio từ vài chục byte đầu file. Chỉ các định dạng
     * MediaRecorder của trình duyệt sinh ra (Chrome/Firefox: webm/ogg, Safari:
     * mp4) và vài định dạng phổ biến mà API chuyển giọng nói nhận.
     */
    public static function sniffAudioExtension(string $head): ?string
    {
        return match (true) {
            str_starts_with($head, "\x1A\x45\xDF\xA3")                         => 'webm', // EBML (WebM/Matroska)
            str_starts_with($head, 'OggS')                                     => 'ogg',
            substr($head, 4, 4) === 'ftyp'                                     => 'mp4',  // MP4/M4A (Safari)
            str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WAVE'   => 'wav',
            str_starts_with($head, 'fLaC')                                     => 'flac',
            str_starts_with($head, 'ID3')
                || (strlen($head) >= 2 && ord($head[0]) === 0xFF && (ord($head[1]) & 0xE0) === 0xE0) => 'mp3',
            default                                                            => null,
        };
    }
}
