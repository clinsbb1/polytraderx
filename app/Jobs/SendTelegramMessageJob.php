<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public int $userId,
        public string $message
    ) {}

    public function handle(TelegramBotService $telegram): void
    {
        $sent = $telegram->sendToUser($this->userId, $this->message);

        if (! $sent) {
            throw new \RuntimeException('Telegram API send failed');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('simulator')->warning('Queued Telegram notification failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
