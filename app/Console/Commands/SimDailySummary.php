<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiDecision;
use App\Models\BalanceSnapshot;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SimDailySummary extends Command
{
    protected $signature = 'sim:daily-summary';
    protected $description = 'Compile daily stats and send Telegram summary (timezone-aware)';

    public function handle(
        UserBotRunner $runner,
        NotificationService $notifications,
    ): int {
        $hasTelegramNotifiedAt = Schema::hasTable('daily_summaries')
            && Schema::hasColumn('daily_summaries', 'telegram_notified_at');

        $hasBalanceColumns = Schema::hasTable('daily_summaries')
            && Schema::hasColumn('daily_summaries', 'starting_balance');

        $results = $runner->runForEachUser(function ($user) use ($notifications, $hasTelegramNotifiedAt, $hasBalanceColumns) {
            $timezone = $this->resolveUserTimezone($user->timezone ?? null);
            $nowLocal = now()->setTimezone($timezone);

            // Give a small post-midnight buffer so last-minute trades have time to resolve.
            $readyAt = $nowLocal->copy()->startOfDay()->addMinutes(30);
            if ($nowLocal->lt($readyAt)) {
                return [
                    'status' => 'waiting_for_local_day_close',
                    'timezone' => $timezone,
                    'local_now' => $nowLocal->format('Y-m-d H:i'),
                ];
            }

            $summaryDate = $nowLocal->copy()->subDay()->toDateString();
            [$rangeStart, $rangeEnd] = $this->localDateToStorageRange($summaryDate, $timezone);

            // Wait if there are trades from that local day still not resolved.
            $pendingResolutions = Trade::forUser($user->id)
                ->whereIn('status', ['pending', 'open'])
                ->whereBetween('market_end_time', [$rangeStart, $rangeEnd])
                ->count();
            if ($pendingResolutions > 0) {
                return [
                    'status' => 'waiting_for_trade_resolution',
                    'timezone' => $timezone,
                    'date' => $summaryDate,
                    'pending' => $pendingResolutions,
                ];
            }

            // Check if summary already exists
            $existing = DailySummary::forUser($user->id)
                ->whereDate('date', $summaryDate)
                ->first();

            if ($existing) {
                $wasQueued = false;
                if ($hasTelegramNotifiedAt && $existing->telegram_notified_at === null) {
                    $wasQueued = $notifications->notifyDailySummary($existing, $user);
                    if ($wasQueued) {
                        $existing->update(['telegram_notified_at' => now()]);
                    }
                }

                return [
                    'status' => $wasQueued ? 'already_exists_notification_queued' : 'already_exists',
                    'summary_id' => $existing->id,
                    'timezone' => $timezone,
                    'date' => $summaryDate,
                ];
            }

            // Calculate stats for the user's previous local day
            $trades = Trade::forUser($user->id)
                ->whereBetween('resolved_at', [$rangeStart, $rangeEnd])
                ->get();

            $totalTrades = $trades->count();
            $wins = $trades->where('status', 'won')->count();
            $losses = $trades->where('status', 'lost')->count();
            $winRate = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0;
            $grossPnl = (float) $trades->whereNotNull('pnl')->sum('pnl');

            // AI cost for the day
            $aiCost = (float) AiDecision::forUser($user->id)
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->sum('cost_usd');

            $netPnl = round($grossPnl - $aiCost, 2);

            // Best and worst trades
            $bestTrade = $trades->whereNotNull('pnl')->sortByDesc('pnl')->first();
            $worstTrade = $trades->whereNotNull('pnl')->sortBy('pnl')->first();

            // Starting balance: last snapshot at or before the day's start.
            // Ending balance: last snapshot at or before the day's end.
            // If a reset happened mid-day, ending_balance naturally reflects the
            // post-reset equity, while gross_pnl only captures trade activity.
            $startingBalance = null;
            $endingBalance = null;
            if ($hasBalanceColumns) {
                $startingSnap = BalanceSnapshot::forUser($user->id)
                    ->where('snapshot_at', '<=', $rangeStart)
                    ->orderBy('snapshot_at', 'desc')
                    ->first();
                $endingSnap = BalanceSnapshot::forUser($user->id)
                    ->where('snapshot_at', '<=', $rangeEnd)
                    ->orderBy('snapshot_at', 'desc')
                    ->first();
                $startingBalance = $startingSnap ? (float) $startingSnap->total_equity : null;
                $endingBalance   = $endingSnap   ? (float) $endingSnap->total_equity   : null;
            }

            $summaryPayload = [
                'user_id' => $user->id,
                'date' => $summaryDate,
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
            ];
            if ($hasTelegramNotifiedAt) {
                $summaryPayload['telegram_notified_at'] = null;
            }
            if ($hasBalanceColumns) {
                $summaryPayload['starting_balance'] = $startingBalance;
                $summaryPayload['ending_balance']   = $endingBalance;
            }

            $summary = DailySummary::create($summaryPayload);

            $queued = $notifications->notifyDailySummary($summary, $user);
            if ($queued && $hasTelegramNotifiedAt) {
                $summary->update(['telegram_notified_at' => now()]);
            }

            return [
                'status' => 'created',
                'summary_id' => $summary->id,
                'timezone' => $timezone,
                'date' => $summaryDate,
                'trades' => $totalTrades,
                'pnl' => $grossPnl,
                'notification_queued' => $queued,
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $meta = '';
                if (isset($r['date'], $r['timezone'])) {
                    $meta = " [{$r['date']} {$r['timezone']}]";
                }
                $details = isset($r['trades']) ? " ({$r['trades']} trades, \${$r['pnl']} P&L)" : '';
                $this->info("User #{$userId}: {$r['status']}{$meta}{$details}");
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }

    private function resolveUserTimezone(?string $timezone): string
    {
        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            return $timezone;
        }

        return (string) config('app.timezone', 'UTC');
    }

    /**
     * Convert a user's local calendar date into DB storage timezone boundaries.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function localDateToStorageRange(string $localDate, string $userTimezone): array
    {
        $storageTimezone = (string) config('app.timezone', 'UTC');

        $start = Carbon::parse($localDate, $userTimezone)
            ->startOfDay()
            ->setTimezone($storageTimezone);

        $end = Carbon::parse($localDate, $userTimezone)
            ->endOfDay()
            ->setTimezone($storageTimezone);

        return [$start, $end];
    }
}
