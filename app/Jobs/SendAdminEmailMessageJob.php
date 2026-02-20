<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\BrandedNotificationMail;
use App\Models\AdminEmailMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAdminEmailMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 30;

    public function __construct(public int $adminEmailMessageId) {}

    public function handle(): void
    {
        $message = AdminEmailMessage::query()->find($this->adminEmailMessageId);

        if (! $message) {
            return;
        }

        if ($message->status === 'sent') {
            return;
        }

        $message->forceFill([
            'status' => 'processing',
            'attempts' => (int) $message->attempts + 1,
            'last_attempt_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $mail = new BrandedNotificationMail(
                subjectLine: (string) $message->subject,
                headline: (string) $message->headline,
                lines: $this->normalizeLines($message->lines),
                actionText: $message->action_text ?: null,
                actionUrl: $message->action_url ?: null,
                smallPrint: $message->small_print ?: null,
            );

            Mail::to((string) $message->recipient_email)->send($mail);

            $message->forceFill([
                'status' => 'sent',
                'success' => true,
                'error_message' => null,
                'sent_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $isFinalAttempt = $this->attempts() >= $this->tries;

            $message->forceFill([
                'status' => $isFinalAttempt ? 'failed' : 'pending',
                'success' => false,
                'error_message' => $e->getMessage(),
                'sent_at' => null,
            ])->save();

            Log::channel('simulator')->warning('Admin announcement email send failed', [
                'admin_email_message_id' => $message->id,
                'recipient_user_id' => $message->recipient_user_id,
                'recipient_email' => $message->recipient_email,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $message = AdminEmailMessage::query()->find($this->adminEmailMessageId);

        if (! $message || $message->status === 'sent') {
            return;
        }

        $message->forceFill([
            'status' => 'failed',
            'success' => false,
            'error_message' => $exception->getMessage(),
            'sent_at' => null,
            'last_attempt_at' => now(),
        ])->save();
    }

    /**
     * @param mixed $lines
     * @return array<int, string>
     */
    private function normalizeLines(mixed $lines): array
    {
        if (! is_array($lines)) {
            return [];
        }

        $normalized = [];
        foreach ($lines as $line) {
            if (! is_string($line)) {
                continue;
            }

            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $normalized[] = $trimmed;
        }

        return $normalized;
    }
}
