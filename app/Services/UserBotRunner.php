<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UserBotRunner
{
    public function getActiveUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('trial_ends_at', '>', now())
                    ->orWhere('subscription_ends_at', '>', now())
                    ->orWhere('is_superadmin', true);
            })
            ->whereHas('credential', function ($query) {
                $query->whereNotNull('polymarket_api_key')
                    ->whereNotNull('polymarket_api_secret')
                    ->whereNotNull('polymarket_api_passphrase')
                    ->whereNotNull('polymarket_wallet_address');
            })
            ->with('credential')
            ->get();
    }

    public function runForEachUser(callable $callback): array
    {
        $users = $this->getActiveUsers();
        $results = [];
        $startTime = microtime(true);

        foreach ($users as $user) {
            try {
                $results[$user->id] = [
                    'status' => 'success',
                    'result' => $callback($user),
                ];

                $user->update(['last_bot_heartbeat' => now()]);
            } catch (\Throwable $e) {
                Log::channel('bot')->error("Bot run failed for user {$user->account_id}", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                try {
                    app(\App\Services\Telegram\NotificationService::class)
                        ->notifyError($e->getMessage(), null, $user);
                } catch (\Throwable) {
                    // Notification failure must never compound the error
                }

                $results[$user->id] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $elapsed = round(microtime(true) - $startTime, 2);
        Log::channel('bot')->info('Bot run completed', [
            'users_processed' => count($users),
            'elapsed_seconds' => $elapsed,
        ]);

        return $results;
    }
}
