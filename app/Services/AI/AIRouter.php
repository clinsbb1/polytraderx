<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
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
    ) {}

    public function getMusclesAnalysis(array $market, array $spotData, int $userId): ?array
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $tiers = $this->getAvailableTiers($userId);
        if (!$tiers['muscles']) {
            return null;
        }

        return $this->muscles->analyze($market, $spotData, $userId);
    }

    public function requestLossAudit(Trade $trade, int $userId): ?AiAudit
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $tiers = $this->getAvailableTiers($userId);
        if (!$tiers['brain']) {
            Log::channel('bot')->debug('Loss audit skipped: plan does not include Brain tier', [
                'user_id' => $userId,
            ]);
            return null;
        }

        return $this->brain->auditLoss($trade, $userId);
    }

    public function requestDailyReview(int $userId): ?AiAudit
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $tiers = $this->getAvailableTiers($userId);
        if (!$tiers['brain']) {
            return null;
        }

        return $this->brain->dailyReview($userId);
    }

    public function requestWeeklyReport(int $userId): ?AiAudit
    {
        if (!$this->anthropic->isConfigured()) {
            return null;
        }

        $tiers = $this->getAvailableTiers($userId);
        if (!$tiers['brain']) {
            return null;
        }

        return $this->brain->weeklyReport($userId);
    }

    public function getAvailableTiers(int $userId): array
    {
        $limits = $this->subscriptionService->getPlanLimits($userId);

        return [
            'reflexes' => true,
            'muscles' => (bool) ($limits['has_ai_muscles'] ?? false),
            'brain' => (bool) ($limits['has_ai_brain'] ?? false),
        ];
    }
}
