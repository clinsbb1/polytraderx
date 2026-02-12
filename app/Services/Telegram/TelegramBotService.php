<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $botToken;
    private string $baseUrl;

    public function __construct(private PlatformSettingsService $platformSettings)
    {
        $this->botToken = (string) $this->platformSettings->get('TELEGRAM_BOT_TOKEN', '');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendToUser(int $userId, string $message): bool
    {
        $user = User::find($userId);

        if (!$user || !$user->hasTelegramLinked()) {
            return false;
        }

        return $this->sendMessage($user->telegram_chat_id, $message);
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        if (empty($this->botToken)) {
            Log::channel('bot')->warning('Telegram bot token not configured');
            return false;
        }

        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful()) {
                Log::channel('bot')->error('Telegram sendMessage failed', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::channel('bot')->error('Telegram sendMessage exception', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function handleWebhookUpdate(array $update): void
    {
        $message = $update['message'] ?? null;

        if (!$message || !isset($message['text'])) {
            return;
        }

        $chatId = (string) $message['chat']['id'];
        $text = trim($message['text']);
        $username = $message['from']['username'] ?? null;

        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $text, $username);
        } elseif ($text === '/unlink') {
            $this->handleUnlink($chatId);
        } elseif ($text === '/status') {
            $this->handleStatus($chatId);
        } elseif ($text === '/help') {
            $this->handleHelp($chatId);
        }
    }

    public function registerWebhook(string $url): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        try {
            $response = Http::post("{$this->baseUrl}/setWebhook", [
                'url' => $url,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::channel('bot')->error('Telegram setWebhook failed', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function handleStart(string $chatId, string $text, ?string $username = null): void
    {
        $parts = explode(' ', $text, 2);
        $accountId = $parts[1] ?? null;

        if (!$accountId) {
            $botUsername = $this->platformSettings->get('TELEGRAM_BOT_USERNAME', 'PolyTraderXBot');
            $this->sendMessage($chatId, "Welcome to PolyTraderX!\n\nTo link your account, send:\n<code>/start YOUR-ACCOUNT-ID</code>\n\nYou can find your Account ID on your Settings > Telegram page in the PolyTraderX dashboard.");
            return;
        }

        $accountId = strtoupper(trim($accountId));

        $user = $this->findUserByAccountId($accountId);

        if (!$user) {
            $this->sendMessage($chatId, "Account ID not found. Please check your Account ID and try again.\n\nFormat: <code>PTX-XXXXXXXXXXXX</code>");
            return;
        }

        if ($user->hasTelegramLinked()) {
            $this->sendMessage($chatId, "This account is already linked to a Telegram account. Please unlink first using /unlink from the linked account, or unlink from your PolyTraderX dashboard.");
            return;
        }

        $this->linkUser($user, $chatId, $username);
        $this->sendMessage($chatId, "Successfully linked to PolyTraderX account <b>{$user->account_id}</b>!\n\nYou will now receive trading notifications here.\n\nCommands:\n/status - Check bot status\n/unlink - Unlink this account\n/help - Show help");
    }

    private function handleUnlink(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "No PolyTraderX account is linked to this Telegram chat.");
            return;
        }

        $user->update([
            'telegram_chat_id' => null,
            'telegram_linked_at' => null,
        ]);

        $this->sendMessage($chatId, "Your PolyTraderX account has been unlinked. You will no longer receive notifications here.");
    }

    private function handleStatus(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "No PolyTraderX account is linked to this Telegram chat.\n\nUse /start YOUR-ACCOUNT-ID to link.");
            return;
        }

        $plan = $user->currentPlan();
        $status = $user->isSubscriptionActive() ? 'Active' : 'Inactive';
        $credentials = $user->hasPolymarketConfigured() ? 'Configured' : 'Not configured';

        $message = "<b>PolyTraderX Status</b>\n\n"
            . "Account: <code>{$user->account_id}</code>\n"
            . "Plan: {$plan?->name ?? 'None'}\n"
            . "Status: {$status}\n"
            . "Polymarket Keys: {$credentials}\n"
            . "Linked: {$user->telegram_linked_at->format('Y-m-d H:i')}";

        $this->sendMessage($chatId, $message);
    }

    private function handleHelp(string $chatId): void
    {
        $this->sendMessage($chatId, "<b>PolyTraderX Bot Commands</b>\n\n"
            . "/start ACCOUNT-ID - Link your account\n"
            . "/status - Check your bot status\n"
            . "/unlink - Unlink your account\n"
            . "/help - Show this help message");
    }

    private function findUserByAccountId(string $accountId): ?User
    {
        return User::where('account_id', $accountId)->first();
    }

    private function linkUser(User $user, string $chatId, ?string $username = null): void
    {
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
            'telegram_linked_at' => now(),
        ]);
    }
}
