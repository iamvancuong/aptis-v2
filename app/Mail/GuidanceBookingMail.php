<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email xác nhận đặt lịch buổi hướng dẫn: thông tin tài khoản + link Zoom.
 */
class GuidanceBookingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public Carbon $sessionAt,
        public string $zoomLink,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Milaedu — Xác nhận buổi hướng dẫn học');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guidance-booking',
            with: [
                'email'     => $this->email,
                'sessionAt' => $this->sessionAt,
                'zoomLink'  => $this->zoomLink,
                'loginUrl'  => route('login'),
            ],
        );
    }
}
