<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminTelegramMessage;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAdminTelegramMessages extends Command
{
    protected $signature = 'telegram:process-admin-messages {--limit=200 : Max pending messages to process per run}';

    protected $description = 'Process queued admin Telegram messages (single + broadcast)';

    public function handle(TelegramBotService $telegram): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $pending = AdminTelegramMessage::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No queued admin Telegram messages.');
            return self::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($pending as $item) {
            $chatId = trim((string) $item->recipient_chat_id);
            $photoPath = null;

            if ($item->image_path) {
                $photoPath = Storage::disk('public')->path($item->image_path);
            }

            try {
                if ($chatId === '') {
                    throw new \RuntimeException('Recipient chat ID is missing');
                }

                $ok = $telegram->sendMessageWithMedia($chatId, (string) $item->message, $photoPath);
                $status = $ok ? 'sent' : 'failed';

                $item->update([
                    'status' => $status,
                    'attempts' => (int) $item->attempts + 1,
                    'last_attempt_at' => now(),
                    'success' => $ok,
                    'error_message' => $ok ? null : 'Telegram API send failed',
                    'sent_at' => $ok ? now() : null,
                ]);

                $ok ? $sent++ : $failed++;
            } catch (\Throwable $e) {
                $item->update([
                    'status' => 'failed',
                    'attempts' => (int) $item->attempts + 1,
                    'last_attempt_at' => now(),
                    'success' => false,
                    'error_message' => $e->getMessage(),
                    'sent_at' => null,
                ]);

                $failed++;

                Log::channel('simulator')->error('Admin Telegram queued send failed', [
                    'admin_telegram_message_id' => $item->id,
                    'recipient_user_id' => $item->recipient_user_id,
                    'recipient_chat_id' => $item->recipient_chat_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed admin Telegram queue. Sent: {$sent}, Failed: {$failed}.");

        return self::SUCCESS;
    }
}

