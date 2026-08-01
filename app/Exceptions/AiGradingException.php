<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Lỗi khi chấm bài bằng AI, có phân loại TẠM THỜI vs VĨNH VIỄN.
 *
 * Vì sao cần phân loại: queue retry 3 lần. Lỗi mạng/429 thì retry là đúng —
 * lượt sau thường qua. Nhưng file audio hỏng, quá 25MB, hay key sai thì retry
 * chỉ tốn tiền và làm học viên chờ lâu hơn mà kết quả vẫn thế.
 *
 * `reason` là mã ngắn để lưu vào `ai_metadata` và đổi ra câu tiếng Việt hiển thị
 * cho học viên — không ném thẳng message kỹ thuật (có thể chứa nội dung phản hồi
 * của OpenAI) ra giao diện.
 */
class AiGradingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $permanent = false,
        public readonly string $reason = 'unknown',
    ) {
        parent::__construct($message);
    }

    /** Retry vô ích — dừng hẳn, báo cho người dùng. */
    public static function permanent(string $reason, string $message): self
    {
        return new self($message, true, $reason);
    }

    /** Có khả năng lượt sau qua — để queue thử lại. */
    public static function retryable(string $reason, string $message): self
    {
        return new self($message, false, $reason);
    }

    /** Câu hiển thị cho học viên. Không lộ chi tiết kỹ thuật. */
    public function userMessage(): string
    {
        return match ($this->reason) {
            'file_missing'   => 'Không tìm thấy file ghi âm của phần này.',
            'empty_file'     => 'File ghi âm rỗng — có thể micro chưa bắt được tiếng.',
            'too_large'      => 'File ghi âm quá dài nên không xử lý tự động được.',
            'no_speech'      => 'Không nhận ra tiếng nói trong bản ghi. Hãy kiểm tra micro và thử ghi lại ở bài sau.',
            'bad_audio'      => 'Bản ghi âm bị lỗi định dạng nên không xử lý được.',
            'config'         => 'Chức năng chấm tự động đang tạm ngưng.',
            default          => 'Chấm tự động chưa hoàn tất cho phần này.',
        };
    }
}
