<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;

class AIRouter
{
    public function __construct(
        private MusclesService $muscles,
        private BrainService $brain,
        private CostTracker $costTracker,
        private AnthropicClient $anthropic,
        private SubscriptionService $subscriptionService,
        private AiUsageLimiter $aiUsageLimiter,
    ) {}

    public function getMusclesAnalysis(array $market, array $spotData, int $userId): ?array
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // Check if Muscles is available for this plan
        $plan = $this->subscriptionService->getUserPlan($user);
        if (!$plan || !$plan->ai_muscles_enabled) {
            return null;
        }

        // Check usage limits
        if (!$this->subscriptionService->canCallMuscles($user)) {
            Log::channel('simulator')->info("User {$user->account_id} hit Muscles AI daily limit");
            return $this->aiUsageLimiter->fallback('AI analysis quota used for this cycle. Core simulation continues normally.');
        }

        $guard = $this->aiUsageLimiter->check($user, 'muscles');
        if (!($guard['allowed'] ?? false)) {
            Log::channel('simulator')->warning('Muscles skipped by limiter', [
                'user_id' => $userId,
                'status' => $guard['status'] ?? 'unknown',
            ]);
            return [
                'status' => $guard['status'] ?? 'ai_limit_reached',
                'message' => $guard['message'] ?? 'AI analysis quota used for this cycle. Core simulation continues normally.',
            ];
        }

        return $this->muscles->analyze($market, $spotData, $userId);
    }

    public function requestLossAudit(Trade $trade, int $userId): AiAudit|array|null
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // Check if Brain is available and within limits
        if (!$this->subscriptionService->canCallBrain($user)) {
            Log::channel('simulator')->info("User {$user->account_id} hit Brain AI limit — skipping audit");
            return [
                'status' => 'ai_limit_reached',
                'message' => 'AI analysis quota used for this cycle. Deep audit limit reached today. Try again tomorrow.',
            ];
        }

        $guard = $this->aiUsageLimiter->check($user, 'brain');
        if (!($guard['allowed'] ?? false)) {
            Log::channel('simulator')->warning('Brain audit skipped by limiter', [
                'user_id' => $userId,
                'status' => $guard['status'] ?? 'unknown',
            ]);
            return [
                'status' => $guard['status'] ?? 'ai_limit_reached',
                'message' => $guard['message'] ?? 'AI analysis quota used for this cycle. Core simulation continues normally.',
            ];
        }

        return $this->brain->auditLoss($trade, $userId);
    }

    public function requestDailyReview(int $userId): AiAudit|array|null
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // Check if Brain is available and within limits
        if (!$this->subscriptionService->canCallBrain($user)) {
            Log::channel('simulator')->info("User {$user->account_id} hit Brain AI limit — skipping daily review");
            return [
                'status' => 'ai_limit_reached',
                'message' => 'AI analysis quota used for this cycle. Deep audit limit reached today. Try again tomorrow.',
            ];
        }

        $guard = $this->aiUsageLimiter->check($user, 'brain');
        if (!($guard['allowed'] ?? false)) {
            return [
                'status' => $guard['status'] ?? 'ai_limit_reached',
                'message' => $guard['message'] ?? 'AI analysis quota used for this cycle. Core simulation continues normally.',
            ];
        }

        return $this->brain->dailyReview($userId);
    }

    public function requestWeeklyReport(int $userId): AiAudit|array|null
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $user = User::find($userId);
        if (!$user) {
            return null;
        }

        // Check if Brain is available and within limits
        if (!$this->subscriptionService->canCallBrain($user)) {
            Log::channel('simulator')->info("User {$user->account_id} hit Brain AI limit — skipping weekly report");
            return [
                'status' => 'ai_limit_reached',
                'message' => 'AI analysis quota used for this cycle. Deep audit limit reached today. Try again tomorrow.',
            ];
        }

        $guard = $this->aiUsageLimiter->check($user, 'brain');
        if (!($guard['allowed'] ?? false)) {
            return [
                'status' => $guard['status'] ?? 'ai_limit_reached',
                'message' => $guard['message'] ?? 'AI analysis quota used for this cycle. Core simulation continues normally.',
            ];
        }

        return $this->brain->weeklyReport($userId);
    }

    public function getAvailableTiers(int $userId): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [
                'reflexes' => true,
                'muscles' => false,
                'brain' => false,
            ];
        }

        $plan = $this->subscriptionService->getUserPlan($user);

        return [
            'reflexes' => true, // Always available
            'muscles' => $plan ? (bool) $plan->ai_muscles_enabled : false,
            'brain' => $plan ? (bool) $plan->ai_brain_enabled : false,
        ];
    }
}
