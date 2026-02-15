<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrandedNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int,string> $lines
     * @param array<string,string> $meta
     */
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $lines = [],
        public ?string $actionText = null,
        public ?string $actionUrl = null,
        public array $meta = [],
        public ?string $smallPrint = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.branded-notification',
        );
    }
}
