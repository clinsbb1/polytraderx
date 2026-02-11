<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    public function getActivePlan(int $userId): ?SubscriptionPlan
    {
        $user = User::find($userId);

        if (!$user || !$user->subscription_plan) {
            return null;
        }

        return SubscriptionPlan::where('slug', $user->subscription_plan)->first();
    }

    public function getPlanLimits(int $userId): array
    {
        $plan = $this->getActivePlan($userId);

        if (!$plan) {
            return [
                'max_daily_trades' => 0,
                'max_concurrent_positions' => 0,
                'has_ai_muscles' => false,
                'has_ai_brain' => false,
            ];
        }

        return [
            'max_daily_trades' => $plan->max_daily_trades,
            'max_concurrent_positions' => $plan->max_concurrent_positions,
            'has_ai_muscles' => $plan->has_ai_muscles,
            'has_ai_brain' => $plan->has_ai_brain,
        ];
    }

    public function isWithinLimits(int $userId, string $limitKey, int $currentCount): bool
    {
        $limits = $this->getPlanLimits($userId);

        if (!isset($limits[$limitKey])) {
            return false;
        }

        $limit = $limits[$limitKey];

        if ($limit === 0 || $limit === null) {
            return true; // 0 or null = unlimited
        }

        return $currentCount < $limit;
    }

    public function isTrialExpired(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user || $user->subscription_plan !== 'free_trial') {
            return false;
        }

        if (!$user->trial_ends_at) {
            return true;
        }

        return Carbon::now()->greaterThan($user->trial_ends_at);
    }

    public function activateSubscription(int $userId, SubscriptionPlan $plan, ?Payment $payment = null): void
    {
        $user = User::findOrFail($userId);

        $endsAt = match ($plan->billing_period) {
            'lifetime' => Carbon::now()->addYears(100),
            'yearly' => Carbon::now()->addDays(365),
            default => Carbon::now()->addDays(30),
        };

        $user->update([
            'subscription_plan' => $plan->slug,
            'subscription_ends_at' => $endsAt,
            'is_active' => true,
        ]);
    }

    public function cancelSubscription(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->update([
            'subscription_plan' => 'free_trial',
            'subscription_ends_at' => null,
        ]);
    }

    public function isSubscriptionExpired(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return true;
        }

        if ($user->subscription_plan === 'free_trial') {
            return $this->isTrialExpired($userId);
        }

        if (!$user->subscription_ends_at) {
            return true;
        }

        return Carbon::now()->greaterThan($user->subscription_ends_at);
    }

    public function grantFreeSubscription(int $userId, string $planSlug, int $durationDays, int $grantedBy): Payment
    {
        $user = User::findOrFail($userId);
        $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();

        $endsAt = Carbon::now()->addDays($durationDays);

        $user->update([
            'subscription_plan' => $plan->slug,
            'subscription_ends_at' => $endsAt,
            'is_active' => true,
        ]);

        return Payment::create([
            'user_id' => $userId,
            'subscription_plan_id' => $plan->id,
            'amount_usd' => '0.00',
            'status' => 'finished',
            'paid_at' => now(),
            'expires_at' => $endsAt,
            'notes' => "Free subscription granted by admin (user #{$grantedBy})",
        ]);
    }

    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::active()->ordered()->get();
    }
}
