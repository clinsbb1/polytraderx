<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\Audit\ForensicsBuilder;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Log;

class BrainService
{
    public function __construct(
        private AnthropicClient $anthropic,
        private PromptBuilder $promptBuilder,
        private CostTracker $costTracker,
        private ForensicsBuilder $forensicsBuilder,
        private PlatformSettingsService $platformSettings,
        private SubscriptionService $subscriptionService,
    ) {}

    public function auditLoss(Trade $trade, int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured()) {
                Log::channel('simulator')->debug('Brain audit skipped: Anthropic not configured');
                return null;
            }

            $forensics = $this->forensicsBuilder->buildForensics($trade);
            $user = \App\Models\User::find($userId);
            $maxPromptTokens = $user ? $this->subscriptionService->getAiMaxTokensPerRequest($user) : 0;
            if ($maxPromptTokens <= 0) {
                $maxPromptTokens = 9000;
            }

            $prompt = $this->promptBuilder->buildBrainAuditPrompt($trade, $forensics, $userId, $maxPromptTokens);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');
            $completionCap = max(512, min(4096, intdiv($maxPromptTokens, 2)));

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], $completionCap);

            $decision = $this->costTracker->recordUsage(
                $userId, $model,
                $response['input_tokens'], $response['output_tokens'],
                'loss_audit',
            );

            $parsed = $this->parseJsonResponse($response['content']);

            $decision->update([
                'prompt' => $prompt['user'],
                'response' => $response['content'],
                'trade_id' => $trade->id,
            ]);

            if ($parsed === null) {
                Log::channel('simulator')->warning('Brain: Failed to parse audit response', [
                    'user_id' => $userId,
                    'trade_id' => $trade->id,
                ]);
                // Keep audited=false so the system can retry later (e.g. temporary provider issues).
                return null;
            }

            $suggestedFixes = $this->sanitizeSuggestedFixes($parsed['suggested_fixes'] ?? []);

            $audit = AiAudit::create([
                'user_id' => $userId,
                'trigger' => 'post_loss',
                'losing_trade_ids' => [$trade->id],
                'analysis' => $parsed['analysis'] ?? $parsed['overall_assessment'] ?? 'No analysis provided',
                'suggested_fixes' => $suggestedFixes,
                'status' => 'pending_review',
                'created_at' => now(),
            ]);

            $trade->update(['audited' => true]);

            Log::channel('simulator')->info('Loss audit complete', [
                'user_id' => $userId,
                'trade_id' => $trade->id,
                'audit_id' => $audit->id,
                'root_cause' => $parsed['root_cause_category'] ?? 'unknown',
                'fixes_count' => count($suggestedFixes),
                'cost' => $decision->cost_usd,
            ]);

            return $audit;
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Brain audit failed', [
                'user_id' => $userId,
                'trade_id' => $trade->id,
                'message' => $e->getMessage(),
            ]);
            // Keep audited=false so failed audits can retry on later runs.
            return null;
        }
    }

    public function dailyReview(int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured()) {
                return null;
            }

            $user = \App\Models\User::find($userId);
            $maxPromptTokens = $user ? $this->subscriptionService->getAiMaxTokensPerRequest($user) : 0;
            if ($maxPromptTokens <= 0) {
                $maxPromptTokens = 9000;
            }

            $prompt = $this->promptBuilder->buildDailyReviewPrompt($userId, $maxPromptTokens);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');
            $completionCap = max(512, min(2048, intdiv($maxPromptTokens, 2)));

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], $completionCap);

            $decision = $this->costTracker->recordUsage(
                $userId, $model,
                $response['input_tokens'], $response['output_tokens'],
                'daily_review',
            );

            $parsed = $this->parseJsonResponse($response['content']);

            $decision->update([
                'prompt' => $prompt['user'],
                'response' => $response['content'],
            ]);

            if ($parsed === null) {
                return null;
            }

            return AiAudit::create([
                'user_id' => $userId,
                'trigger' => 'daily_review',
                'losing_trade_ids' => [],
                'analysis' => $parsed['analysis'] ?? '',
                'suggested_fixes' => $this->sanitizeSuggestedFixes($parsed['suggested_param_changes'] ?? []),
                'status' => 'pending_review',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Daily review failed', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function weeklyReport(int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured()) {
                return null;
            }

            $user = \App\Models\User::find($userId);
            $maxPromptTokens = $user ? $this->subscriptionService->getAiMaxTokensPerRequest($user) : 0;
            if ($maxPromptTokens <= 0) {
                $maxPromptTokens = 9000;
            }

            $prompt = $this->promptBuilder->buildWeeklyReviewPrompt($userId, $maxPromptTokens);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');
            $completionCap = max(512, min(4096, intdiv($maxPromptTokens, 2)));

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], $completionCap);

            $decision = $this->costTracker->recordUsage(
                $userId, $model,
                $response['input_tokens'], $response['output_tokens'],
                'weekly_review',
            );

            $parsed = $this->parseJsonResponse($response['content']);

            $decision->update([
                'prompt' => $prompt['user'],
                'response' => $response['content'],
            ]);

            if ($parsed === null) {
                return null;
            }

            return AiAudit::create([
                'user_id' => $userId,
                'trigger' => 'weekly_review',
                'losing_trade_ids' => [],
                'analysis' => $parsed['analysis'] ?? '',
                'suggested_fixes' => $this->sanitizeSuggestedFixes($parsed['suggested_param_changes'] ?? []),
                'status' => 'pending_review',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Weekly report failed', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function parseJsonResponse(string $content): ?array
    {
        $content = trim($content);

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function sanitizeSuggestedFixes(array $fixes): array
    {
        return collect($fixes)
            ->map(function ($fix) {
                if (!is_array($fix)) {
                    return null;
                }

                $fix['action'] = 'review_required';
                return $fix;
            })
            ->filter()
            ->values()
            ->toArray();
    }
}
