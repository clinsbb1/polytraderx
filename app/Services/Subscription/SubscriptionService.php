<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;

class SubscriptionService
{
    public function getActivePlan(User $user): ?SubscriptionPlan
    {
        if (!$user->subscription_plan) {
            return null;
        }

        return SubscriptionPlan::where('slug', $user->subscription_plan)->first();
    }

    public function getPlanLimits(User $user): array
    {
        $plan = $this->getActivePlan($user);

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

    public function isWithinLimits(User $user, string $limitKey, int $currentCount): bool
    {
        $limits = $this->getPlanLimits($user);

        if (!isset($limits[$limitKey])) {
            return false;
        }

        $limit = $limits[$limitKey];

        if ($limit === 0 || $limit === null) {
            return true; // 0 or null = unlimited
        }

        return $currentCount < $limit;
    }

    public function isTrialExpired(User $user): bool
    {
        if ($user->subscription_plan !== 'free_trial') {
            return false;
        }

        if (!$user->trial_ends_at) {
            return true;
        }

        return Carbon::now()->greaterThan($user->trial_ends_at);
    }

    public function activateSubscription(User $user, SubscriptionPlan $plan, ?Payment $payment = null): void
    {
        $endsAt = Carbon::now()->addDays($plan->billing_period === 'monthly' ? 30 : 365);

        $user->update([
            'subscription_plan' => $plan->slug,
            'subscription_ends_at' => $endsAt,
            'is_active' => true,
        ]);
    }

    public function cancelSubscription(User $user): void
    {
        $user->update([
            'subscription_plan' => 'free_trial',
            'subscription_ends_at' => null,
            'is_active' => false,
        ]);
    }

    public function isSubscriptionExpired(User $user): bool
    {
        if ($user->subscription_plan === 'free_trial') {
            return $this->isTrialExpired($user);
        }

        if (!$user->subscription_ends_at) {
            return true;
        }

        return Carbon::now()->greaterThan($user->subscription_ends_at);
    }

    public function getAvailablePlans(): \Illuminate\Database\Eloquent\Collection
    {
        return SubscriptionPlan::active()->ordered()->get();
    }
}
