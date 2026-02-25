<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\AiDecision;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SubscriptionService
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private PlatformSettingsService $platformSettings,
        private SettingsService $settingsService,
    ) {}

    // ──────────── Plan Resolution ────────────

    public function getUserPlan(User $user): ?SubscriptionPlan
    {
        if (!$user->subscription_plan) {
            return null;
        }

        return Cache::remember(
            "subscription_plan:{$user->subscription_plan}",
            self::CACHE_TTL,
            fn() => SubscriptionPlan::where('slug', $user->subscription_plan)->first()
        );
    }

    public function isActive(User $user): bool
    {
        if ($user->is_superadmin) {
            return true;
        }

        if (!$user->is_active) {
            return false;
        }

        if ($user->subscription_plan === 'free') {
            return $this->isFreeModeEnabled()
                && $user->trial_ends_at !== null
                && $user->trial_ends_at->isFuture();
        }

        // Lifetime: always active
        if ($user->is_lifetime) {
            return true;
        }

        // Paid: check expiry
        if ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    public function isFreeModeEnabled(): bool
    {
        return SubscriptionPlan::isFreeModeEnabled();
    }

    public function isPaid(User $user): bool
    {
        return in_array($user->subscription_plan, ['pro', 'advanced', 'lifetime']);
    }

    public function isLifetime(User $user): bool
    {
        return $user->is_lifetime === true;
    }

    // ──────────── Feature Gates (CRITICAL) ────────────

    public function getMaxSignalsPerDay(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? $plan->max_signals_per_day : 0;
    }

    public function getMaxConcurrentSimulations(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? $plan->max_concurrent_positions : 0;
    }

    public function getMaxConcurrentPositions(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? $plan->max_concurrent_positions : 0;
    }

    public function getMaxAiMusclesPerDay(User $user): int
    {
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return 0;
        }

        return (int) ($plan->ai_muscles_calls_per_day ?? $plan->max_ai_muscles_calls_per_day ?? 0);
    }

    public function getMaxAiBrainPerDay(User $user): int
    {
        $plan = $this->getUserPlan($user);
        if (!$plan) {
            return 0;
        }

        return (int) ($plan->ai_brain_calls_per_day ?? $plan->max_ai_brain_calls_per_day ?? 0);
    }

    public function getMaxAiBrainPerMonth(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? $plan->max_ai_brain_calls_per_month : 0;
    }

    public function getAiMonthlyTokenCap(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (int) ($plan->ai_monthly_token_cap ?? 0) : 0;
    }

    public function getAiMaxTokensPerRequest(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (int) ($plan->ai_max_tokens_per_request ?? 0) : 0;
    }

    public function canExportCsv(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (bool) $plan->csv_export_enabled : false;
    }

    public function canUseStrategyHealth(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (bool) $plan->strategy_health_metrics : false;
    }

    public function canUseTelegram(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (bool) $plan->telegram_enabled : false;
    }

    public function canUseBrain(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (bool) $plan->ai_brain_enabled : false;
    }

    public function getHistoricalDays(User $user): int
    {
        $plan = $this->getUserPlan($user);
        return $plan ? $plan->historical_days : 7;
    }

    public function hasPriorityProcessing(User $user): bool
    {
        $plan = $this->getUserPlan($user);
        return $plan ? (bool) $plan->priority_processing : false;
    }

    // ──────────── Usage Tracking ────────────

    public function getSignalsToday(User $user): int
    {
        return Trade::forUser($user->id)
            ->whereDate('created_at', today())
            ->count();
    }

    public function canSimulateMore(User $user): bool
    {
        $max = $this->getMaxSignalsPerDay($user);
        if ($max === 0) {
            return true; // unlimited
        }

        $current = $this->getSignalsToday($user);
        return $current < $max;
    }

    public function getAiMusclesCallsToday(User $user): int
    {
        return AiDecision::forUser($user->id)
            ->where('tier', 'muscles')
            ->whereDate('created_at', today())
            ->count();
    }

    public function canCallMuscles(User $user): bool
    {
        $max = $this->getMaxAiMusclesPerDay($user);
        if ($max === 0) {
            return true; // unlimited
        }

        $current = $this->getAiMusclesCallsToday($user);
        return $current < $max;
    }

    public function getAiBrainCallsToday(User $user): int
    {
        return AiDecision::forUser($user->id)
            ->where('tier', 'brain')
            ->whereDate('created_at', today())
            ->count();
    }

    public function getAiBrainCallsThisMonth(User $user): int
    {
        return AiDecision::forUser($user->id)
            ->where('tier', 'brain')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    public function canCallBrain(User $user): bool
    {
        // Check if Brain is enabled for this plan
        if (!$this->canUseBrain($user)) {
            return false;
        }

        // Check daily limit
        $maxDaily = $this->getMaxAiBrainPerDay($user);
        if ($maxDaily > 0) {
            $currentDaily = $this->getAiBrainCallsToday($user);
            if ($currentDaily >= $maxDaily) {
                return false;
            }
        }

        // Check monthly limit
        $maxMonthly = $this->getMaxAiBrainPerMonth($user);
        if ($maxMonthly > 0) {
            $currentMonthly = $this->getAiBrainCallsThisMonth($user);
            if ($currentMonthly >= $maxMonthly) {
                return false;
            }
        }

        return true;
    }

    // ──────────── Subscription Management ────────────

    public function activate(User $user, string $planSlug, string $billingInterval, ?int $durationDays = null): void
    {
        $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();

        $updateData = [
            'subscription_plan' => $planSlug,
            'billing_interval' => $billingInterval,
            'is_active' => true,
        ];

        if ($billingInterval === 'lifetime') {
            $updateData['is_lifetime'] = true;
            $updateData['subscription_ends_at'] = null; // never expires

            // Increment sold count
            $plan->increment('lifetime_sold');
        } elseif ($billingInterval === 'yearly') {
            $days = $durationDays ?? 365;
            $updateData['subscription_ends_at'] = now()->addDays($days);
        } elseif ($billingInterval === 'monthly') {
            $days = $durationDays ?? 30;
            $updateData['subscription_ends_at'] = now()->addDays($days);
        } elseif ($billingInterval === 'free') {
            $updateData['subscription_ends_at'] = null;
        }

        $user->update($updateData);
    }

    public function renew(User $user): void
    {
        if ($user->is_lifetime) {
            return; // lifetime doesn't renew
        }

        if ($user->billing_interval === 'free') {
            return; // free doesn't renew
        }

        $days = match ($user->billing_interval) {
            'yearly' => 365,
            'monthly' => 30,
            default => 30,
        };

        // If currently active, extend from current end date
        // If expired, extend from now
        $baseDate = ($user->subscription_ends_at && $user->subscription_ends_at->isFuture())
            ? $user->subscription_ends_at
            : now();

        $user->update([
            'subscription_ends_at' => $baseDate->addDays($days),
        ]);
    }

    public function expire(User $user): void
    {
        // Stop the simulator immediately on expiry, regardless of path.
        $this->settingsService->set('SIMULATOR_ENABLED', 'false', 'system', $user->id);

        if ($this->isFreeModeEnabled()) {
            // Downgrade to free plan (indefinite free access — trial_ends_at stays null).
            $user->update([
                'subscription_plan' => 'free',
                'billing_interval' => 'free',
                'is_lifetime' => false,
                'is_active' => true,
                'subscription_ends_at' => null,
                'trial_ends_at' => null,
            ]);

            return;
        }

        // Free mode disabled: deactivate until renewal.
        $user->update([
            'is_lifetime' => false,
            'billing_interval' => null,
            'is_active' => false,
            'subscription_ends_at' => null,
            'trial_ends_at' => null,
        ]);
    }

    public function canPurchaseLifetime(): bool
    {
        $plan = SubscriptionPlan::where('slug', 'lifetime')->where('is_active', true)->first();

        if (!$plan) {
            return false;
        }

        if (!$plan->lifetime_cap) {
            return true; // no cap set
        }

        return $plan->lifetime_sold < $plan->lifetime_cap;
    }

    public function getLifetimeRemaining(): int
    {
        $plan = SubscriptionPlan::where('slug', 'lifetime')->first();

        if (!$plan || !$plan->lifetime_cap) {
            return 999; // essentially unlimited
        }

        return max(0, $plan->lifetime_cap - $plan->lifetime_sold);
    }

    public function grantFree(User $user, string $planSlug, ?int $durationDays = null): Payment
    {
        $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();

        $billingInterval = match ($plan->billing_period) {
            'lifetime' => 'lifetime',
            'yearly' => 'yearly',
            default => 'monthly',
        };

        $this->activate($user, $planSlug, $billingInterval, $durationDays);

        return Payment::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'billing_interval' => $billingInterval,
            'amount_usd' => 0.00,
            'status' => 'finished',
            'paid_at' => now(),
            'expires_at' => $user->subscription_ends_at,
            'notes' => 'Complimentary subscription granted by admin (user #' . auth()->id() . ')',
        ]);
    }

    public function getAvailablePlans(): Collection
    {
        return SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function getPlanBySlug(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $slug)->first();
    }

    public function activateSubscription(int $userId, SubscriptionPlan $plan, Payment $payment): ?\Illuminate\Support\Carbon
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $billingInterval = $payment->billing_interval ?: match ($plan->billing_period) {
            'lifetime' => 'lifetime',
            'yearly' => 'yearly',
            default => 'monthly',
        };

        $this->activate($user, $plan->slug, $billingInterval);
        $user->refresh();

        return $user->subscription_ends_at;
    }

    public function grantFreeSubscription(int $userId, string $planSlug, int $durationDays, ?int $grantedByUserId = null): Payment
    {
        $user = User::findOrFail($userId);
        $payment = $this->grantFree($user, $planSlug, $durationDays);

        if ($grantedByUserId !== null) {
            $payment->update([
                'notes' => "Complimentary subscription granted by admin (user #{$grantedByUserId})",
            ]);
        }

        return $payment;
    }
}
