<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSupportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $topic,
        public string $issueMessage,
        public ?string $screenshotPath = null,
        public ?string $screenshotName = null,
        public ?string $screenshotMime = null,
    ) {}

    public function envelope(): Envelope
    {
        $subject = sprintf(
            '[Support] %s - %s (%s)',
            ucfirst($this->topic),
            $this->user->name,
            $this->user->account_id
        );

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-support');
    }

    public function attachments(): array
    {
        if (!$this->screenshotPath) {
            return [];
        }

        return [
            Attachment::fromPath(storage_path('app/' . $this->screenshotPath))
                ->as($this->screenshotName ?: basename($this->screenshotPath))
                ->withMime($this->screenshotMime ?: 'application/octet-stream'),
        ];
    }
}
