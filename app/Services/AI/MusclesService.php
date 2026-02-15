<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Log;

class MusclesService
{
    public function __construct(
        private AnthropicClient $anthropic,
        private PromptBuilder $promptBuilder,
        private CostTracker $costTracker,
        private PlatformSettingsService $platformSettings,
    ) {}

    public function analyze(array $market, array $spotData, int $userId): ?array
    {
        try {
            if (!$this->anthropic->isConfigured()) {
                Log::channel('simulator')->debug('Muscles skipped: Anthropic not configured');
                return null;
            }

            if ($this->costTracker->isOverBudget($userId)) {
                Log::channel('simulator')->warning('Muscles skipped: AI budget exceeded', ['user_id' => $userId]);
                return null;
            }

            $prompt = $this->promptBuilder->buildMusclesPrompt($market, $spotData, $userId);
            $model = $this->platformSettings->get('AI_MUSCLES_MODEL', 'claude-haiku-4-5-20251001');

            $response = $this->anthropic->sendMessage($model, $prompt['system'], $prompt['user'], 1024);

            $decision = $this->costTracker->recordUsage(
                $userId,
                $model,
                $response['input_tokens'],
                $response['output_tokens'],
                'market_analysis',
            );

            $parsed = $this->parseJsonResponse($response['content']);

            $decision->update([
                'prompt' => $prompt['user'],
                'response' => $response['content'],
            ]);

            if ($parsed === null) {
                Log::channel('simulator')->warning('Muscles: Failed to parse AI response', [
                    'user_id' => $userId,
                    'response' => substr($response['content'], 0, 500),
                ]);
                return null;
            }

            Log::channel('simulator')->info('Muscles analysis complete', [
                'user_id' => $userId,
                'asset' => $market['asset'] ?? 'unknown',
                'side' => $parsed['side'] ?? 'unknown',
                'confidence' => $parsed['confidence'] ?? 0,
                'cost' => $decision->cost_usd,
            ]);

            return $parsed;
        } catch (\Exception $e) {
            Log::channel('simulator')->error('Muscles analysis failed', [
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

        // Try extracting from markdown code blocks
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $content, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try extracting first JSON object
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
