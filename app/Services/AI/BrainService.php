<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\Audit\ForensicsBuilder;
use App\Services\Audit\StrategyUpdater;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Log;

class BrainService
{
    public function __construct(
        private AnthropicClient $anthropic,
        private PromptBuilder $promptBuilder,
        private CostTracker $costTracker,
        private ForensicsBuilder $forensicsBuilder,
        private SettingsService $settings,
        private PlatformSettingsService $platformSettings,
    ) {}

    public function auditLoss(Trade $trade, int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured()) {
                Log::channel('bot')->debug('Brain audit skipped: Anthropic not configured');
                return null;
            }

            if ($this->costTracker->isOverBudget($userId)) {
                Log::channel('bot')->warning('Brain audit skipped: AI budget exceeded', ['user_id' => $userId]);
                return null;
            }

            $forensics = $this->forensicsBuilder->buildForensics($trade);
            $prompt = $this->promptBuilder->buildBrainAuditPrompt($trade, $forensics, $userId);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], 4096);

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
                Log::channel('bot')->warning('Brain: Failed to parse audit response', [
                    'user_id' => $userId,
                    'trade_id' => $trade->id,
                ]);
                $trade->update(['audited' => true]);
                return null;
            }

            $suggestedFixes = $parsed['suggested_fixes'] ?? [];
            $autoApply = $this->settings->getBool('AI_AUTO_APPLY_FIXES', false, $userId);
            $hasAutoFixes = collect($suggestedFixes)->contains(fn($f) => ($f['action'] ?? '') === 'auto_apply');

            $status = ($autoApply && $hasAutoFixes) ? 'auto_applied' : 'pending_review';

            $audit = AiAudit::create([
                'user_id' => $userId,
                'trigger' => 'post_loss',
                'losing_trade_ids' => [$trade->id],
                'analysis' => $parsed['analysis'] ?? $parsed['overall_assessment'] ?? 'No analysis provided',
                'suggested_fixes' => $suggestedFixes,
                'status' => $status,
                'created_at' => now(),
            ]);

            if ($autoApply && !empty($suggestedFixes)) {
                $updater = app(StrategyUpdater::class);
                $applied = $updater->autoApplyFixes($audit, $userId);
                Log::channel('bot')->info("Auto-applied {$applied} fixes from audit", [
                    'user_id' => $userId,
                    'audit_id' => $audit->id,
                ]);
            }

            $trade->update(['audited' => true]);

            Log::channel('bot')->info('Loss audit complete', [
                'user_id' => $userId,
                'trade_id' => $trade->id,
                'audit_id' => $audit->id,
                'root_cause' => $parsed['root_cause_category'] ?? 'unknown',
                'fixes_count' => count($suggestedFixes),
                'cost' => $decision->cost_usd,
            ]);

            return $audit;
        } catch (\Exception $e) {
            Log::channel('bot')->error('Brain audit failed', [
                'user_id' => $userId,
                'trade_id' => $trade->id,
                'message' => $e->getMessage(),
            ]);
            $trade->update(['audited' => true]);
            return null;
        }
    }

    public function dailyReview(int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured() || $this->costTracker->isOverBudget($userId)) {
                return null;
            }

            $prompt = $this->promptBuilder->buildDailyReviewPrompt($userId);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], 2048);

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
                'suggested_fixes' => $parsed['suggested_param_changes'] ?? [],
                'status' => 'pending_review',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('bot')->error('Daily review failed', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function weeklyReport(int $userId): ?AiAudit
    {
        try {
            if (!$this->anthropic->isConfigured() || $this->costTracker->isOverBudget($userId)) {
                return null;
            }

            $prompt = $this->promptBuilder->buildWeeklyReviewPrompt($userId);
            $model = $this->platformSettings->get('AI_BRAIN_MODEL', 'claude-sonnet-4-5-20250929');

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], 4096);

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
                'suggested_fixes' => $parsed['suggested_param_changes'] ?? [],
                'status' => 'pending_review',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('bot')->error('Weekly report failed', [
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
}
