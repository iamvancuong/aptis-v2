<?php

namespace App\Mail;

use App\Models\ClassSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email nhắc học viên sắp tới giờ lớp online.
 *
 * KHÔNG đính link phòng Meet vào email: link chỉ được trả qua cổng kiểm soát
 * `/lop-hoc/{id}/join` sau khi kiểm tra hạn tài khoản và khung giờ. Email chỉ
 * dẫn về trang lớp học — chuyển tiếp email cho người khác cũng vô dụng.
 */
class ClassSessionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ClassSession $classSession) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sắp tới giờ học: ' . $this->classSession->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.class-session-reminder',
            with: [
                'title'     => $this->classSession->title,
                'timeLabel' => $this->classSession->timeLabel(),
                'moTa'      => $this->classSession->description,
                'classUrl'  => route('classes.index'),
            ],
        );
    }
}
