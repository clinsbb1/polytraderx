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
        $tradeLogs = 0;
        $snapshots = 0;
        $aiDecisions = 0;
        $orphaned = 0;
        $botActivity = 0;

        // 1. Delete market scan logs older than 24 hours (run first; this is user-visible retention)
        try {
            $botActivity = BotActivityLog::whereRaw('created_at < (UTC_TIMESTAMP() - INTERVAL 24 HOUR)')->delete();
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Cleanup failed for market scan logs', [
                'message' => $e->getMessage(),
            ]);
        }

        // 2. Delete trade_logs older than 90 days
        try {
            $tradeLogs = TradeLog::where('created_at', '<', now()->subDays(90))->delete();
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Cleanup failed for trade logs', [
                'message' => $e->getMessage(),
            ]);
        }

        // 3. Delete balance_snapshots older than 180 days
        try {
            $snapshots = BalanceSnapshot::where('snapshot_at', '<', now()->subDays(180))->delete();
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Cleanup failed for balance snapshots', [
                'message' => $e->getMessage(),
            ]);
        }

        // 4. Delete ai_decisions older than 90 days for resolved trades
        try {
            $aiDecisions = AiDecision::where('created_at', '<', now()->subDays(90))
                ->whereHas('trade', function ($query) {
                    $query->whereIn('status', ['won', 'lost', 'cancelled', 'expired']);
                })
                ->delete();
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Cleanup failed for AI decisions with trades', [
                'message' => $e->getMessage(),
            ]);
        }

        // 5. Clean orphaned ai_decisions (no trade) older than 90 days
        try {
            $orphaned = AiDecision::where('created_at', '<', now()->subDays(90))
                ->whereNull('trade_id')
                ->delete();
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Cleanup failed for orphaned AI decisions', [
                'message' => $e->getMessage(),
            ]);
        }

        $totalAi = (int) $aiDecisions + (int) $orphaned;

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
