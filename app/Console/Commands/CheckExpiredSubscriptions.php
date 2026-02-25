<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Email\LifecycleEmailService;
use App\Services\Settings\SettingsService;
use App\Services\Subscription\SubscriptionService;
use App\Services\Telegram\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Warn users about expiring subscriptions and deactivate expired ones';

    public function handle(
        NotificationService $notifications,
        SettingsService $settings,
        LifecycleEmailService $emails,
        SubscriptionService $subscriptionService,
    ): int {
        $warned3Day = 0;
        $warned1Day = 0;
        $deactivated = 0;
        $freeModeEnabled = $subscriptionService->isFreeModeEnabled();

        // 1. Warn users 3 days before expiry
        $expiringSoon = User::where('is_active', true)
            ->where(function ($q) use ($freeModeEnabled) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '>', now())
                        ->where('subscription_ends_at', '<=', now()->addDays(3));
                });

                if ($freeModeEnabled) {
                    $q->orWhere(function ($q2) {
                        $q2->where('subscription_plan', 'free')
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now())
                            ->where('trial_ends_at', '<=', now()->addDays(3));
                    });
                }
            })
            ->get();

        foreach ($expiringSoon as $user) {
            $cacheKey = "expiry_warned:{$user->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $expiresAt = $user->subscription_ends_at ?? $user->trial_ends_at;
            $daysLeft = (int) now()->diffInDays($expiresAt, false);
            $daysLeft = max($daysLeft, 1);

            $notifications->notifySubscriptionExpiring($user, $daysLeft);
            $emails->sendSubscriptionExpiring($user, $daysLeft, $expiresAt);
            Cache::put($cacheKey, true, now()->addDays(3));
            $warned3Day++;
        }

        // 2. Urgent warning: 1 day before expiry
        $expiringTomorrow = User::where('is_active', true)
            ->where(function ($q) use ($freeModeEnabled) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('subscription_ends_at')
                        ->where('subscription_ends_at', '>', now())
                        ->where('subscription_ends_at', '<=', now()->addDay());
                });

                if ($freeModeEnabled) {
                    $q->orWhere(function ($q2) {
                        $q2->where('subscription_plan', 'free')
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now())
                            ->where('trial_ends_at', '<=', now()->addDay());
                    });
                }
            })
            ->get();

        foreach ($expiringTomorrow as $user) {
            $urgentKey = "expiry_urgent:{$user->id}:" . today()->toDateString();
            if (Cache::has($urgentKey)) {
                continue;
            }

            $notifications->notifySubscriptionExpiring($user, 1);
            $emails->sendSubscriptionExpiring($user, 1, $user->subscription_ends_at ?? $user->trial_ends_at);
            Cache::put($urgentKey, true, now()->addDay());
            $warned1Day++;
        }

        // 3. Deactivate expired trials
        $expiredTrials = $freeModeEnabled
            ? User::where('subscription_plan', 'free')
                ->where('is_active', true)
                ->where('trial_ends_at', '<', now())
                ->get()
            : collect();

        foreach ($expiredTrials as $user) {
            $expiredKey = "subscription_expired_notified:{$user->id}:trial";
            if (!Cache::has($expiredKey)) {
                $emails->sendSubscriptionExpired($user, $user->subscription_plan);
                Cache::put($expiredKey, true, now()->addDays(30));
            }

            $user->update(['is_active' => false]);
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
            $notifications->notifySubscriptionExpired($user);

            Log::channel('bot')->info("Trial expired for user {$user->id} ({$user->email})");
            $deactivated++;
        }

        // 3b. If free mode is disabled, deactivate all active free-plan users immediately.
        if (!$freeModeEnabled) {
            $legacyFreeUsers = User::where('subscription_plan', 'free')
                ->where('is_active', true)
                ->get();

            foreach ($legacyFreeUsers as $user) {
                $expiredKey = "subscription_expired_notified:{$user->id}:free_disabled";
                if (!Cache::has($expiredKey)) {
                    $emails->sendFreeAccessRevoked($user);
                    Cache::put($expiredKey, true, now()->addDays(30));
                }

                $user->update([
                    'is_active' => false,
                    'billing_interval' => null,
                    'trial_ends_at' => null,
                    'subscription_ends_at' => null,
                ]);
                $settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
                $notifications->notifySubscriptionExpired($user);

                Log::channel('bot')->info("Free mode disabled; access revoked for user {$user->id} ({$user->email})");
                $deactivated++;
            }
        }

        // 4. Deactivate expired subscriptions
        $expiredSubscriptions = User::where('subscription_plan', '!=', 'free')
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $user) {
            $expiredKey = "subscription_expired_notified:{$user->id}:paid";
            if (!Cache::has($expiredKey)) {
                $emails->sendSubscriptionExpired($user, $user->subscription_plan);
                Cache::put($expiredKey, true, now()->addDays(30));
            }

            $subscriptionService->expire($user);
            $user->refresh();
            $settings->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);
            $notifications->notifySubscriptionExpired($user);

            Log::channel('bot')->info("Subscription expired for user {$user->id} ({$user->email})");
            if (!$user->is_active) {
                $deactivated++;
            }
        }

        $this->info("Warned: {$warned3Day} (3-day), {$warned1Day} (1-day). Deactivated: {$deactivated}.");

        return Command::SUCCESS;
    }
}
