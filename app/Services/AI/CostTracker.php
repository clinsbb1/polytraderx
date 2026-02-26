<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AiUsage;
use App\Models\AiDecision;
use App\Services\Settings\PlatformSettingsService;
use Carbon\Carbon;

class CostTracker
{
    private const PRICING = [
        'claude-haiku-4-5-20251001' => ['input' => 0.25, 'output' => 1.25],
        'claude-sonnet-4-5-20250929' => ['input' => 3.00, 'output' => 15.00],
    ];

    public function __construct(private PlatformSettingsService $platformSettings) {}

    public function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = self::PRICING[$model] ?? null;

        if ($pricing === null) {
            // Fallback: use Haiku pricing for unknown models
            $pricing = ['input' => 0.25, 'output' => 1.25];
        }

        $cost = ($inputTokens / 1_000_000) * $pricing['input']
            + ($outputTokens / 1_000_000) * $pricing['output'];

        return round($cost, 6);
    }

    public function recordUsage(
        int $userId,
        string $model,
        int $inputTokens,
        int $outputTokens,
        string $decisionType,
    ): AiDecision {
        $tier = str_contains($model, 'haiku') ? 'muscles' : 'brain';
        $cost = $this->calculateCost($model, $inputTokens, $outputTokens);
        $totalTokens = $inputTokens + $outputTokens;
        $month = now()->format('Y-m');

        $decision = AiDecision::create([
            'user_id' => $userId,
            'tier' => $tier,
            'model_used' => $model,
            'tokens_input' => $inputTokens,
            'tokens_output' => $outputTokens,
            'cost_usd' => $cost,
            'decision_type' => $decisionType,
            'created_at' => now(),
        ]);

        $usage = AiUsage::firstOrCreate(
            ['user_id' => $userId, 'month' => $month],
            [
                'tokens_input' => 0,
                'tokens_output' => 0,
                'total_tokens' => 0,
                'total_cost_usd' => 0,
            ]
        );

        $usage->increment('tokens_input', $inputTokens);
        $usage->increment('tokens_output', $outputTokens);
        $usage->increment('total_tokens', $totalTokens);
        $usage->increment('total_cost_usd', $cost);

        return $decision;
    }

    public function getMonthlyUsage(int $userId, ?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');

        $usage = AiUsage::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->first();

        if (!$usage) {
            return [
                'tokens_input' => 0,
                'tokens_output' => 0,
                'total_tokens' => 0,
                'total_cost_usd' => 0.0,
            ];
        }

        return [
            'tokens_input' => (int) $usage->tokens_input,
            'tokens_output' => (int) $usage->tokens_output,
            'total_tokens' => (int) $usage->total_tokens,
            'total_cost_usd' => (float) $usage->total_cost_usd,
        ];
    }

    public function getMonthlySpend(?int $userId = null): float
    {
        $query = AiDecision::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($userId !== null) {
            $query->forUser($userId);
        }

        return (float) $query->sum('cost_usd');
    }

    public function getDailySpend(?int $userId = null): float
    {
        $query = AiDecision::query()
            ->whereDate('created_at', today());

        if ($userId !== null) {
            $query->forUser($userId);
        }

        return (float) $query->sum('cost_usd');
    }

    public function getSpendByTier(?int $userId = null): array
    {
        $query = AiDecision::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($userId !== null) {
            $query->forUser($userId);
        }

        $results = $query->selectRaw('tier, SUM(cost_usd) as total')
            ->groupBy('tier')
            ->pluck('total', 'tier')
            ->toArray();

        return [
            'muscles' => (float) ($results['muscles'] ?? 0),
            'brain' => (float) ($results['brain'] ?? 0),
        ];
    }

    public function isOverBudget(int $userId): bool
    {
        $budget = $this->platformSettings->getFloat('AI_MONTHLY_BUDGET', 100.0);
        $spent = $this->getMonthlySpend($userId);

        return $spent >= $budget;
    }

    public function getSpendByDay(int $days = 30, ?int $userId = null): array
    {
        $query = AiDecision::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, SUM(cost_usd) as cost')
            ->groupBy('date')
            ->orderBy('date');

        if ($userId !== null) {
            $query->forUser($userId);
        }

        return $query->get()->map(fn($row) => [
            'date' => $row->date,
            'cost' => (float) $row->cost,
        ])->toArray();
    }

    public function getSpendByUser(int $limit = 20): array
    {
        return AiDecision::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->join('users', 'ai_decisions.user_id', '=', 'users.id')
            ->selectRaw('ai_decisions.user_id, users.account_id, SUM(ai_decisions.cost_usd) as cost')
            ->groupBy('ai_decisions.user_id', 'users.account_id')
            ->orderByDesc('cost')
            ->limit($limit)
            ->get()
            ->map(fn($row) => [
                'user_id' => $row->user_id,
                'account_id' => $row->account_id,
                'cost' => (float) $row->cost,
            ])->toArray();
    }
}
