<?php

declare(strict_types=1);

namespace App\Services\Trading;

use App\Models\Trade;
use App\Models\TradeLog;
use App\Models\User;
use App\Services\Polymarket\OrderService;
use App\Services\Polymarket\PolymarketClient;
use App\Services\Settings\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TradeExecutor
{
    public function __construct(private SettingsService $settings) {}

    public function execute(array $signal, array $market, array $spotData, User $user): ?Trade
    {
        $side = $signal['side'];
        $entryPrice = $side === 'YES'
            ? (float) $market['yes_price']
            : (float) $market['no_price'];

        $tokenId = $side === 'YES'
            ? ($market['yes_token_id'] ?? '')
            : ($market['no_token_id'] ?? '');

        $amount = (float) ($signal['bet_amount'] ?? 0);
        if (!is_finite($amount) || $amount <= 0) {
            Log::channel('simulator')->warning('Trade execution skipped due to invalid bet amount', [
                'user_id' => $user->id,
                'market_id' => $market['condition_id'] ?? null,
                'bet_amount' => $signal['bet_amount'] ?? null,
            ]);

            return null;
        }

        if (!is_finite($entryPrice) || $entryPrice <= 0.0) {
            Log::channel('simulator')->warning('Trade execution skipped due to invalid entry price', [
                'user_id' => $user->id,
                'market_id' => $market['condition_id'] ?? null,
                'asset' => $market['asset'] ?? null,
                'side' => $side,
                'entry_price' => $entryPrice,
                'yes_price' => $market['yes_price'] ?? null,
                'no_price' => $market['no_price'] ?? null,
            ]);

            try {
                app(\App\Services\Telegram\NotificationService::class)
                    ->notifyInvalidEntryPrice($entryPrice, $market, $user);
            } catch (\Throwable) {
                // Notification failure must never crash trading
            }

            return null;
        }

        $potentialPayout = round($amount / $entryPrice, 2);

        // 1. Create trade record as pending
        $trade = Trade::create([
            'user_id' => $user->id,
            'market_id' => $market['condition_id'] ?? '',
            'market_slug' => $market['slug'] ?? '',
            'market_question' => $market['question'] ?? '',
            'asset' => $market['asset'] ?? '',
            'side' => $side,
            'entry_price' => $entryPrice,
            'amount' => $amount,
            'potential_payout' => $potentialPayout,
            'status' => 'pending',
            'confidence_score' => $signal['confidence'],
            'decision_tier' => $signal['decision_tier'],
            'decision_reasoning' => $signal,
            'external_spot_at_entry' => $spotData['spot_price'] ?? null,
            'market_end_time' => $this->resolveMarketEndTime($market),
            'entry_at' => now(),
            'audited' => false,
        ]);

        // 2. Place order
        try {
            $client = new PolymarketClient($user);
            $orderService = new OrderService($client, $this->settings, $user->id, app(\App\Services\Polymarket\Eip712SignerService::class));
            $orderResult = $orderService->placeOrder($tokenId, 'BUY', $entryPrice, $amount);

            // 3. Update trade to open
            $reasoning = $trade->decision_reasoning;
            $reasoning['order_result'] = $orderResult;
            $trade->update([
                'status' => 'open',
                'decision_reasoning' => $reasoning,
            ]);

            // 4. Create forensic trade log
            TradeLog::create([
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'event' => 'placed',
                'data' => [
                    'trade_id' => $trade->id,
                    'timestamp' => now()->toIso8601String(),
                    'market' => [
                        'condition_id' => $market['condition_id'] ?? '',
                        'question' => $market['question'] ?? '',
                        'asset' => $market['asset'] ?? '',
                        'end_time' => (string) ($market['end_time'] ?? ''),
                        'seconds_remaining' => $market['seconds_remaining'] ?? 0,
                    ],
                    'decision' => [
                        'tier' => $signal['decision_tier'],
                        'confidence' => $signal['confidence'],
                        'side' => $side,
                        'reasoning' => $signal['reasoning'],
                    ],
                    'risk' => [
                        'bet_amount' => $amount,
                        'entry_price' => $entryPrice,
                        'potential_payout' => $potentialPayout,
                        'daily_pnl_before' => $signal['risk_check']['checks'] ?? [],
                    ],
                    'order' => $orderResult,
                    'external_data' => [
                        'spot_price' => $spotData['spot_price'] ?? null,
                        'price_at_open' => $spotData['price_at_open'] ?? null,
                        'change_pct' => $spotData['change_since_open_pct'] ?? null,
                        'change_1m' => $spotData['change_1m_pct'] ?? null,
                        'change_5m' => $spotData['change_5m_pct'] ?? null,
                    ],
                ],
                'created_at' => now(),
            ]);

            Log::channel('simulator')->info('Trade executed', [
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'asset' => $market['asset'] ?? '',
                'side' => $side,
                'amount' => $amount,
                'entry_price' => $entryPrice,
                'dry_run' => $orderResult['dry_run'] ?? false,
            ]);

            try {
                app(\App\Services\Telegram\NotificationService::class)->notifyTradeExecuted($trade);
            } catch (\Exception $e) {
                // Notification failure must never crash trading
            }

            try {
                app(\App\Services\Trading\SimulationBalanceService::class)->snapshotForUser($user->id);
            } catch (\Throwable) {
                // Snapshot failure must never crash trade execution.
            }

            return $trade;
        } catch (\Exception $e) {
            // Order failed — mark trade as cancelled
            $trade->update(['status' => 'cancelled']);

            TradeLog::create([
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'event' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toIso8601String(),
                ],
                'created_at' => now(),
            ]);

            Log::channel('simulator')->error('Trade execution failed', [
                'user_id' => $user->id,
                'trade_id' => $trade->id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function resolveMarketEndTime(array $market): ?Carbon
    {
        $secondsRemaining = $market['seconds_remaining'] ?? null;
        if (is_numeric($secondsRemaining)) {
            $seconds = (int) round((float) $secondsRemaining);
            if ($seconds > 0 && $seconds <= 7200) {
                // Prefer countdown-based end time because it reflects the exact active round.
                return now()->addSeconds($seconds);
            }
        }

        $endTime = $market['end_time'] ?? null;

        if ($endTime instanceof \DateTimeInterface) {
            return Carbon::instance($endTime);
        }

        if (is_scalar($endTime)) {
            $trimmed = trim((string) $endTime);
            if ($trimmed !== '') {
                try {
                    return Carbon::parse($trimmed);
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }
}
