<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email gửi thông tin tài khoản sau khi thanh toán thành công.
 * Tài khoản mới: kèm mật khẩu mặc định. Gia hạn: chỉ báo hạn mới.
 */
class AccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $email,
        public ?string $password,
        public bool $isNew,
        public ?Carbon $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isNew
                ? 'Tài khoản Milaedu của bạn đã sẵn sàng'
                : 'Milaedu — tài khoản đã được gia hạn',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.account-credentials',
            with: [
                'email'     => $this->email,
                'password'  => $this->password,
                'isNew'     => $this->isNew,
                'expiresAt' => $this->expiresAt,
                'loginUrl'  => route('login'),
            ],
        );
    }
}
