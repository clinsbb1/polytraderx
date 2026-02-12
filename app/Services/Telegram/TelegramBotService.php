<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\BalanceSnapshot;
use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
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
        } elseif ($text === '/today') {
            $this->handleToday($chatId);
        } elseif ($text === '/balance') {
            $this->handleBalance($chatId);
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
        $this->sendMessage($chatId, "Successfully linked to PolyTraderX account <b>{$user->account_id}</b>!\n\nYou will now receive trading notifications here.\n\nCommands:\n/status - Bot status & today's stats\n/today - Today's trades\n/balance - Current balance\n/unlink - Unlink this account\n/help - Show help");
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

        try {
            $settings = app(SettingsService::class);
            $isDryRun = $settings->getBool('DRY_RUN', true, $user->id);
            $botEnabled = $settings->getBool('BOT_ENABLED', true, $user->id);
        } catch (\Exception) {
            $isDryRun = true;
            $botEnabled = false;
        }

        $todayTrades = Trade::forUser($user->id)->whereDate('created_at', today())->count();
        $todayPnl = (float) Trade::forUser($user->id)->whereDate('resolved_at', today())->sum('pnl');
        $openPositions = Trade::forUser($user->id)->open()->count();

        $statusEmoji = $botEnabled ? '🟢 Active' : '🔴 Paused';
        $mode = $isDryRun ? '📝 DRY RUN' : '💰 LIVE';
        $pnlFormatted = $todayPnl >= 0 ? "+\$" . number_format($todayPnl, 2) : "-\$" . number_format(abs($todayPnl), 2);

        $plan = $user->currentPlan();
        $planName = $plan ? $plan->name : 'None';

        $message = "<b>Bot Status</b>\n\n"
            . "Status: {$statusEmoji}\n"
            . "Mode: {$mode}\n"
            . "Today: {$todayTrades} trades, {$pnlFormatted}\n"
            . "Open positions: {$openPositions}\n"
            . "Plan: {$planName}";

        $this->sendMessage($chatId, $message);
    }

    private function handleToday(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "No account linked. Use /start YOUR-ACCOUNT-ID to link.");
            return;
        }

        $trades = Trade::forUser($user->id)
            ->whereDate('created_at', today())
            ->latest()
            ->take(10)
            ->get();

        if ($trades->isEmpty()) {
            $this->sendMessage($chatId, "No trades today yet.");
            return;
        }

        $lines = ["<b>Today's Trades</b>\n"];
        foreach ($trades as $t) {
            $emoji = match ($t->status) {
                'won' => '✅',
                'lost' => '❌',
                'open' => '⏳',
                default => '⬜',
            };
            $pnl = $t->pnl !== null
                ? ((float) $t->pnl >= 0 ? "+\$" . number_format((float) $t->pnl, 2) : "-\$" . number_format(abs((float) $t->pnl), 2))
                : 'pending';
            $lines[] = "{$emoji} {$t->asset} {$t->side} — {$pnl}";
        }

        $this->sendMessage($chatId, implode("\n", $lines));
    }

    private function handleBalance(string $chatId): void
    {
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->sendMessage($chatId, "No account linked. Use /start YOUR-ACCOUNT-ID to link.");
            return;
        }

        $snapshot = BalanceSnapshot::forUser($user->id)->latest('snapshot_at')->first();

        if ($snapshot) {
            $message = "<b>Balance</b>\n\n"
                . "USDC: \$" . number_format((float) $snapshot->balance_usdc, 2) . "\n"
                . "Open positions: \$" . number_format((float) $snapshot->open_positions_value, 2) . "\n"
                . "Total equity: \$" . number_format((float) $snapshot->total_equity, 2) . "\n"
                . "As of: " . $snapshot->snapshot_at->diffForHumans();
            $this->sendMessage($chatId, $message);
        } else {
            $this->sendMessage($chatId, "No balance data yet. Make sure your Polymarket credentials are configured.");
        }
    }

    private function handleHelp(string $chatId): void
    {
        $this->sendMessage($chatId, "<b>PolyTraderX Commands</b>\n\n"
            . "/start ACCOUNT-ID - Link your account\n"
            . "/status - Bot status & today's stats\n"
            . "/today - Today's trades\n"
            . "/balance - Current balance\n"
            . "/unlink - Unlink account\n"
            . "/help - This message");
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
