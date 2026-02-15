<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiDecision;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class SimDailySummary extends Command
{
    protected $signature = 'sim:daily-summary';
    protected $description = 'Compile daily stats and send Telegram summary';

    public function handle(
        UserBotRunner $runner,
        NotificationService $notifications,
    ): int {
        $yesterday = now()->subDay()->toDateString();

        $results = $runner->runForEachUser(function ($user) use ($yesterday, $notifications) {
            // Check if summary already exists
            $existing = DailySummary::forUser($user->id)
                ->whereDate('date', $yesterday)
                ->first();

            if ($existing) {
                return ['status' => 'already_exists', 'summary_id' => $existing->id];
            }

            // Calculate stats for yesterday
            $trades = Trade::forUser($user->id)
                ->whereDate('resolved_at', $yesterday)
                ->get();

            $totalTrades = $trades->count();
            $wins = $trades->where('status', 'won')->count();
            $losses = $trades->where('status', 'lost')->count();
            $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0;
            $grossPnl = (float) $trades->whereNotNull('pnl')->sum('pnl');

            // AI cost for the day
            $aiCost = (float) AiDecision::forUser($user->id)
                ->whereDate('created_at', $yesterday)
                ->sum('cost_usd');

            $netPnl = round($grossPnl - $aiCost, 2);

            // Best and worst trades
            $bestTrade = $trades->whereNotNull('pnl')->sortByDesc('pnl')->first();
            $worstTrade = $trades->whereNotNull('pnl')->sortBy('pnl')->first();

            $summary = DailySummary::create([
                'user_id' => $user->id,
                'date' => $yesterday,
                'total_trades' => $totalTrades,
                'wins' => $wins,
                'losses' => $losses,
                'win_rate' => $winRate,
                'gross_pnl' => $grossPnl,
                'net_pnl' => $netPnl,
                'ai_cost_usd' => $aiCost,
                'best_trade_id' => $bestTrade?->id,
                'worst_trade_id' => $worstTrade?->id,
                'created_at' => now(),
            ]);

            $notifications->notifyDailySummary($summary, $user);

            return [
                'status' => 'created',
                'summary_id' => $summary->id,
                'trades' => $totalTrades,
                'pnl' => $grossPnl,
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info("User #{$userId}: {$r['status']}" . (isset($r['trades']) ? " ({$r['trades']} trades, \${$r['pnl']} P&L)" : ''));
            }
        }

        if (empty($results)) {
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
