<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Cache;
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
        private PlatformSettingsService $platformSettings,
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

        $conditionId = (string) ($market['condition_id'] ?? '');
        $resultCacheKey = $conditionId !== '' ? "muscles:{$userId}:{$conditionId}" : null;
        $cooldownCacheKey = $conditionId !== '' ? "muscles:cooldown:{$userId}:{$conditionId}" : null;
        $cacheTtlSeconds = max(60, $this->platformSettings->getInt('AI_MUSCLES_CACHE_TTL_SECONDS', 900));
        $failureCooldownSeconds = max(30, $this->platformSettings->getInt('AI_MUSCLES_FAILURE_COOLDOWN_SECONDS', 300));

        if ($resultCacheKey !== null) {
            $cached = Cache::get($resultCacheKey);
            if (is_array($cached) && isset($cached['confidence'])) {
                return $cached;
            }
        }

        if ($cooldownCacheKey !== null && Cache::has($cooldownCacheKey)) {
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

        // Avoid duplicate paid calls when pre-analysis and evaluation overlap.
        if ($conditionId !== '') {
            $lock = Cache::lock("muscles:lock:{$userId}:{$conditionId}", 10);
            if (!$lock->get()) {
                return null;
            }

            try {
                if ($resultCacheKey !== null) {
                    $cached = Cache::get($resultCacheKey);
                    if (is_array($cached) && isset($cached['confidence'])) {
                        return $cached;
                    }
                }

                $analysis = $this->muscles->analyze($market, $spotData, $userId);
                if ($resultCacheKey !== null && is_array($analysis) && isset($analysis['confidence'])) {
                    Cache::put($resultCacheKey, $analysis, $cacheTtlSeconds);
                    return $analysis;
                }

                if ($cooldownCacheKey !== null) {
                    // Back off briefly on null/failed parses to prevent rapid repeated spend.
                    Cache::put($cooldownCacheKey, true, $failureCooldownSeconds);
                }

                return $analysis;
            } finally {
                optional($lock)->release();
            }
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
