<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Gửi học viên link vào phòng Zoom (join_url) trước buổi hướng dẫn. */
class GuidanceLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Carbon $sessionAt,
        public string $joinUrl,
        public ?string $passcode,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Milaedu — Link buổi hướng dẫn ' . $this->sessionAt->format('d/m'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guidance-link',
            with: [
                'sessionAt' => $this->sessionAt,
                'joinUrl'   => $this->joinUrl,
                'passcode'  => $this->passcode,
            ],
        );
    }
}
