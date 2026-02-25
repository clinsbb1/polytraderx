<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UserBotRunner
{
    public function __construct(
        private SettingsService $settings,
        private SubscriptionService $subscriptionService,
    )
    {
    }

    public function getActiveUsers(): Collection
    {
        $freeModeEnabled = $this->subscriptionService->isFreeModeEnabled();

        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($freeModeEnabled) {
                $query->where('subscription_ends_at', '>', now())
                    ->orWhere('is_lifetime', true)
                    ->orWhere('is_superadmin', true);

                if ($freeModeEnabled) {
                    $query->orWhere(function ($freeAccess) {
                        $freeAccess->where('subscription_plan', 'free')
                            ->where(function ($q) {
                                $q->whereNull('trial_ends_at') // null = indefinite free access
                                  ->orWhere('trial_ends_at', '>', now()); // or active trial
                            });
                    });
                }
            })
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

                $simulatorEnabled = $this->settings->getBool('SIMULATOR_ENABLED', false, $user->id);
                if ($simulatorEnabled) {
                    $user->update(['last_bot_heartbeat' => now()]);
                }
            } catch (\Throwable $e) {
                Log::channel('simulator')->error("Bot run failed for user {$user->account_id}", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                report($e);

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
        Log::channel('simulator')->info('Bot run completed', [
            'users_processed' => count($users),
            'elapsed_seconds' => $elapsed,
        ]);

        return $results;
    }
}
