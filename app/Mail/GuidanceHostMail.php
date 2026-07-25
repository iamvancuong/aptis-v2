<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Gửi admin (người dạy) link mở phòng (start_url) + danh sách học viên. */
class GuidanceHostMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Carbon $sessionAt,
        public string $startUrl,
        public ?string $passcode,
        public array $studentEmails = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[Milaedu] Mở phòng dạy ' . $this->sessionAt->format('d/m H:i'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guidance-host',
            with: [
                'sessionAt'     => $this->sessionAt,
                'startUrl'      => $this->startUrl,
                'passcode'      => $this->passcode,
                'studentEmails' => $this->studentEmails,
            ],
        );
    }
}
