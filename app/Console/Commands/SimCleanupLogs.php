<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiDecision;
use App\Models\BalanceSnapshot;
use App\Models\BotActivityLog;
use App\Models\TradeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SimCleanupLogs extends Command
{
    protected $signature = 'sim:cleanup-logs';
    protected $description = 'Delete old trade logs and prune stale data';

    public function handle(): int
    {
        // 1. Delete trade_logs older than 90 days
        $tradeLogs = TradeLog::where('created_at', '<', now()->subDays(90))->delete();

        // 2. Delete balance_snapshots older than 180 days
        $snapshots = BalanceSnapshot::where('snapshot_at', '<', now()->subDays(180))->delete();

        // 3. Delete ai_decisions older than 90 days for resolved trades
        $aiDecisions = AiDecision::where('created_at', '<', now()->subDays(90))
            ->whereHas('trade', function ($query) {
                $query->whereIn('status', ['won', 'lost', 'cancelled', 'expired']);
            })
            ->delete();

        // 4. Delete bot activity logs older than 30 days
        $botActivity = BotActivityLog::where('created_at', '<', now()->subDays(30))->delete();

        // Also clean orphaned ai_decisions (no trade) older than 90 days
        $orphaned = AiDecision::where('created_at', '<', now()->subDays(90))
            ->whereNull('trade_id')
            ->delete();

        $totalAi = $aiDecisions + $orphaned;

        Log::channel('simulator')->info('Cleanup completed', [
            'trade_logs' => $tradeLogs,
            'snapshots' => $snapshots,
            'ai_decisions' => $totalAi,
            'bot_activity_logs' => $botActivity,
        ]);

        $this->info("Cleaned up {$tradeLogs} trade_logs, {$snapshots} snapshots, {$totalAi} ai_decisions, {$botActivity} bot_activity_logs.");

        return Command::SUCCESS;
    }
}
