<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Models\AiAudit;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;

class NotificationFormatter
{
    public function formatTradeExecuted(Trade $trade): string
    {
        $remaining = $trade->decision_reasoning['order_result']['seconds_remaining']
            ?? ($trade->market_end_time ? now()->diffInSeconds($trade->market_end_time) : '?');

        $potential = number_format((float) $trade->potential_payout, 2);
        $confidence = round((float) $trade->confidence_score * 100);

        return "🟢 Signal Evaluated\n\n"
            . "<b>{$trade->asset} {$trade->side}</b> @ \${$trade->entry_price}\n"
            . "Amount: \${$trade->amount} → Potential: \${$potential}\n"
            . "Confidence: {$confidence}% ({$trade->decision_tier})\n"
            . "Window: {$remaining}s remaining";
    }

    public function formatTradeResolved(Trade $trade): string
    {
        $pnl = (float) $trade->pnl;

        if ($trade->status === 'won') {
            $pnlFormatted = '+$' . number_format(abs($pnl), 2);
            return "✅ Simulation Won\n\n"
                . "<b>{$trade->asset} {$trade->side}</b> — {$pnlFormatted}\n"
                . "Entry: \${$trade->entry_price} → Resolved: \${$trade->exit_price}";
        }

        $pnlFormatted = '-$' . number_format(abs($pnl), 2);
        return "❌ Simulation Lost\n\n"
            . "<b>{$trade->asset} {$trade->side}</b> — {$pnlFormatted}\n"
            . "Entry: \${$trade->entry_price} → Resolved: \${$trade->exit_price}";
    }

    public function formatDailySummary(DailySummary $summary, User $user): string
    {
        $pnl = (float) $summary->gross_pnl;
        $pnlEmoji = $pnl >= 0 ? '🟢' : '🔴';
        $pnlFormatted = $pnl >= 0 ? '+$' . number_format($pnl, 2) : '-$' . number_format(abs($pnl), 2);

        $netPnl = (float) $summary->net_pnl;
        $netFormatted = $netPnl >= 0 ? '+$' . number_format($netPnl, 2) : '-$' . number_format(abs($netPnl), 2);

        // Balance line: starting → ending (only if recorded)
        $balanceLine = '';
        $startBal = $summary->starting_balance !== null ? (float) $summary->starting_balance : null;
        $endBal   = $summary->ending_balance   !== null ? (float) $summary->ending_balance   : null;
        if ($startBal !== null && $endBal !== null) {
            $balanceLine = "\nBalance: \${$startBal} → \${$endBal}";
        }

        $bestLine = '';
        if ($summary->best_trade_id) {
            $best = $summary->bestTrade;
            if ($best) {
                $bestLine = "\nBest: {$best->asset} {$best->side} +\$" . number_format(abs((float) $best->pnl), 2);
            }
        }

        $worstLine = '';
        if ($summary->worst_trade_id) {
            $worst = $summary->worstTrade;
            if ($worst) {
                $worstLine = "\nWorst: {$worst->asset} {$worst->side} -\$" . number_format(abs((float) $worst->pnl), 2);
            }
        }

        return "📊 Daily Summary — {$summary->date->format('Y-m-d')}\n\n"
            . "Trades: {$summary->total_trades} ({$summary->wins}W / {$summary->losses}L)\n"
            . "Win Rate: {$summary->win_rate}%\n"
            . "P&L: {$pnlEmoji} {$pnlFormatted}\n"
            . "Net P&L: {$netFormatted}"
            . $balanceLine
            . $bestLine
            . $worstLine;
    }

    public function formatWeeklyReport(array $weekData, User $user): string
    {
        $pnl = $weekData['net_pnl'] ?? 0;
        $pnlFormatted = $pnl >= 0 ? '+$' . number_format($pnl, 2) : '-$' . number_format(abs($pnl), 2);

        $dailyBreakdown = '';
        foreach ($weekData['daily'] ?? [] as $day) {
            $dayPnl = $day['pnl'] >= 0 ? "+\${$day['pnl']}" : "-\$" . number_format(abs($day['pnl']), 2);
            $dailyBreakdown .= "\n{$day['label']}: {$day['trades']} trades, {$dayPnl}";
        }

        $insight = '';
        if (!empty($weekData['top_insight'])) {
            $insight = "\n\nTop insight: {$weekData['top_insight']}";
        }

        return "📈 Weekly Report — {$weekData['start_date']} to {$weekData['end_date']}\n\n"
            . "Total Trades: " . ($weekData['total_trades'] ?? 0) . "\n"
            . "Win Rate: " . ($weekData['win_rate'] ?? 0) . "%\n"
            . "Net P&L: {$pnlFormatted}\n"
            . "AI Cost: \$" . number_format($weekData['ai_cost'] ?? 0, 2) . "\n"
            . "Audits: " . ($weekData['audits_count'] ?? 0) . " losses analyzed"
            . ($dailyBreakdown ? "\n\nDaily Breakdown:" . $dailyBreakdown : '')
            . $insight;
    }

    public function formatLossAudit(AiAudit $audit, Trade $trade): string
    {
        $fixes = $audit->suggested_fixes ?? [];
        $fixCount = count($fixes);
        $analysis = is_string($audit->analysis) ? $audit->analysis : json_encode($audit->analysis);
        $analysis = substr($analysis, 0, 300);

        $fixLines = '';
        foreach (array_slice($fixes, 0, 3) as $i => $fix) {
            $prefix = $i === min($fixCount - 1, 2) ? '└' : '├';
            $param = $fix['param_key'] ?? '?';
            $current = $fix['current_value'] ?? '?';
            $suggested = $fix['suggested_value'] ?? '?';
            $fixLines .= "\n{$prefix} {$param}: {$current} → {$suggested}";
        }

        $appUrl = config('app.url', 'https://polytraderx.xyz');

        return "🔍 Loss Audited — {$trade->asset} {$trade->side}\n\n"
            . "Analysis: {$analysis}\n\n"
            . "Suggested fixes: {$fixCount}"
            . $fixLines
            . ($fixCount > 0 ? "\n\nReview → {$appUrl}/audits/{$audit->id}" : '');
    }

    public function formatBalanceAlert(float $balance, float $threshold, User $user): string
    {
        return "⚠️ Low Balance Alert\n\n"
            . "Current balance: \$" . number_format($balance, 2) . "\n"
            . "Threshold: \$" . number_format($threshold, 2) . "\n\n"
            . "Consider pausing or reducing bet sizes.";
    }

    public function formatInvalidEntryPrice(float $entryPrice, array $market, User $user): string
    {
        $appUrl = config('app.url', 'https://polytraderx.xyz');
        $asset = (string) ($market['asset'] ?? 'Market');
        $marketLabel = trim((string) ($market['question'] ?? ''));
        $entryPriceFormatted = number_format($entryPrice, 4);

        return "⚠️ Trade Skipped\n\n"
            . "An invalid entry price (\${$entryPriceFormatted}) was detected for {$asset}.\n"
            . ($marketLabel !== '' ? "Market: {$marketLabel}\n" : '')
            . "\nPlease update/reset your simulated balance in your account before continuing.\n"
            . "Balance: {$appUrl}/balance";
    }

    public function formatDrawdownAlert(float $dailyPnl, float $drawdownPct, float $threshold, User $user): string
    {
        return "🔴 Drawdown Alert\n\n"
            . "Today's P&L: -\$" . number_format(abs($dailyPnl), 2) . "\n"
            . "Drawdown: " . number_format($drawdownPct, 1) . "% (threshold: " . number_format($threshold, 1) . "%)\n\n"
            . "Simulator is still active. Adjust in Strategy settings.";
    }

    public function formatErrorAlert(string $error, ?string $context, User $user): string
    {
        return "⛔ Simulator Error\n\n"
            . "An internal simulator error occurred.\n"
            . "Admin has been notified and a fix is being worked on.\n\n"
            . "The simulator will retry automatically.";
    }

    public function formatSubscriptionActivated(User $user, string $planName, ?Carbon $expiresAt): string
    {
        $expires = $expiresAt ? $expiresAt->format('Y-m-d') : 'Lifetime';

        return "✅ Subscription Activated!\n\n"
            . "Plan: {$planName}\n"
            . "Expires: {$expires}\n\n"
            . "Happy simulating!";
    }

    public function formatSubscriptionExpiring(User $user, int $daysLeft): string
    {
        $appUrl = config('app.url', 'https://polytraderx.xyz');
        $plan = $user->subscription_plan ?? 'Your';

        return "⏰ Subscription Expiring\n\n"
            . "Your {$plan} plan expires in {$daysLeft} day(s).\n"
            . "Renew at {$appUrl}/subscription";
    }

    public function formatSubscriptionExpired(User $user): string
    {
        $appUrl = config('app.url', 'https://polytraderx.xyz');

        return "❌ Subscription Expired\n\n"
            . "Your simulator has been paused.\n"
            . "Renew at {$appUrl}/subscription to resume simulating.";
    }

    public function formatBotPaused(string $reason, User $user): string
    {
        return "⏸ Simulator Paused\n\n"
            . "Reason: {$reason}\n"
            . "Re-enable in Strategy settings or dashboard.";
    }

    public function formatWelcome(User $user): string
    {
        $appUrl = config('app.url', 'https://polytraderx.xyz');

        return "👋 Welcome to PolyTraderX!\n\n"
            . "Your account (<code>{$user->account_id}</code>) is linked.\n\n"
            . "You'll receive:\n"
            . "• Signal alerts (if enabled)\n"
            . "• Daily P&L summaries\n"
            . "• AI audit reports\n"
            . "• Balance warnings\n\n"
            . "Manage notifications: {$appUrl}/settings/notifications";
    }
}
