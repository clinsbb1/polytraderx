<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiDecision;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Subscription\SubscriptionService;

class AiUsageLimiter
{
    public function __construct(
        private CostTracker $costTracker,
        private SubscriptionService $subscriptionService,
        private PlatformSettingsService $platformSettings,
    ) {}

    public function check(User $user, string $tier): array
    {
        $globalBudget = $this->platformSettings->getFloat('AI_MONTHLY_BUDGET', 100.0);
        $platformSpend = $this->costTracker->getMonthlySpend();

        if ($globalBudget > 0 && $platformSpend >= $globalBudget) {
            return [
                'allowed' => false,
                'status' => 'ai_budget_paused',
                'message' => 'AI analysis temporarily paused due to monthly budget cycle. Core simulation continues normally.',
            ];
        }

        $usage = $this->costTracker->getMonthlyUsage($user->id);
        $monthlyTokenCap = $this->subscriptionService->getAiMonthlyTokenCap($user);
        $nextRequestEstimate = max(1000, $this->subscriptionService->getAiMaxTokensPerRequest($user));

        if ($monthlyTokenCap > 0 && ($usage['total_tokens'] >= $monthlyTokenCap || ($usage['total_tokens'] + $nextRequestEstimate) > $monthlyTokenCap)) {
            return [
                'allowed' => false,
                'status' => 'ai_limit_reached',
                'message' => 'AI analysis quota used for this cycle. Core simulation continues normally. Upgrade plan for extended AI analysis.',
            ];
        }

        if ($tier === 'brain') {
            $brainDailyCap = $this->subscriptionService->getMaxAiBrainPerDay($user);
            $brainToday = AiDecision::forUser($user->id)
                ->where('tier', 'brain')
                ->whereDate('created_at', today())
                ->count();

            if ($brainDailyCap > 0 && $brainToday >= $brainDailyCap) {
                return [
                    'allowed' => false,
                    'status' => 'ai_limit_reached',
                    'message' => 'AI analysis quota used for this cycle. Deep audit limit reached today. Try again tomorrow.',
                ];
            }
        }

        if ($tier === 'muscles') {
            $musclesDailyCap = $this->subscriptionService->getMaxAiMusclesPerDay($user);
            $musclesToday = AiDecision::forUser($user->id)
                ->where('tier', 'muscles')
                ->whereDate('created_at', today())
                ->count();

            if ($musclesDailyCap > 0 && $musclesToday >= $musclesDailyCap) {
                return [
                    'allowed' => false,
                    'status' => 'ai_limit_reached',
                    'message' => 'AI analysis quota used for this cycle. Core simulation continues normally.',
                ];
            }
        }

        return ['allowed' => true];
    }

    public function fallback(string $message): array
    {
        return [
            'status' => 'ai_limit_reached',
            'message' => $message,
        ];
    }
}
