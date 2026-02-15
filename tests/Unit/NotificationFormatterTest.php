<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\AiAudit;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Models\User;
use App\Services\Telegram\NotificationFormatter;
use Tests\TestCase;

class NotificationFormatterTest extends TestCase
{
    private NotificationFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new NotificationFormatter();
    }

    public function test_format_trade_executed_contains_details(): void
    {
        $trade = new Trade([
            'asset' => 'BTC',
            'side' => 'YES',
            'entry_price' => 0.96,
            'amount' => 5.00,
            'potential_payout' => 5.21,
            'confidence_score' => 0.95,
            'decision_tier' => 'muscles',
            'decision_reasoning' => ['order_result' => ['dry_run' => true, 'seconds_remaining' => 42]],
        ]);

        $result = $this->formatter->formatTradeExecuted($trade);

        $this->assertStringContainsString('Signal Evaluated', $result);
        $this->assertStringContainsString('BTC YES', $result);
        $this->assertStringContainsString('5.00', $result);
        $this->assertStringContainsString('95%', $result);
        $this->assertLessThanOrEqual(4096, strlen($result));
    }

    public function test_format_trade_resolved_win(): void
    {
        $trade = new Trade([
            'asset' => 'BTC',
            'side' => 'YES',
            'entry_price' => 0.96,
            'exit_price' => 1.00,
            'status' => 'won',
            'pnl' => 0.21,
        ]);

        $result = $this->formatter->formatTradeResolved($trade);

        $this->assertStringContainsString('Simulation Won', $result);
        $this->assertStringContainsString('+$0.21', $result);
        $this->assertStringContainsString('BTC YES', $result);
    }

    public function test_format_trade_resolved_loss(): void
    {
        $trade = new Trade([
            'asset' => 'ETH',
            'side' => 'NO',
            'entry_price' => 0.04,
            'exit_price' => 0.00,
            'status' => 'lost',
            'pnl' => -5.00,
        ]);

        $result = $this->formatter->formatTradeResolved($trade);

        $this->assertStringContainsString('Simulation Lost', $result);
        $this->assertStringContainsString('-$5.00', $result);
        $this->assertStringContainsString('ETH NO', $result);
    }

    public function test_format_daily_summary_includes_stats(): void
    {
        $summary = new DailySummary([
            'date' => '2026-02-12',
            'total_trades' => 10,
            'wins' => 8,
            'losses' => 2,
            'win_rate' => 80.00,
            'gross_pnl' => 12.30,
            'net_pnl' => 11.85,
            'ai_cost_usd' => 0.45,
        ]);

        $user = new User(['name' => 'Test']);

        $result = $this->formatter->formatDailySummary($summary, $user);

        $this->assertStringContainsString('Daily Summary', $result);
        $this->assertStringContainsString('8W / 2L', $result);
        $this->assertStringContainsString('80', $result);
        $this->assertStringContainsString('+$12.30', $result);
        $this->assertLessThanOrEqual(4096, strlen($result));
    }

    public function test_format_loss_audit_includes_fixes(): void
    {
        $audit = new AiAudit([
            'analysis' => 'Price reversal in final seconds due to large whale sell',
            'suggested_fixes' => [
                ['param_key' => 'MIN_CONFIDENCE_SCORE', 'current_value' => '0.92', 'suggested_value' => '0.95'],
                ['param_key' => 'ENTRY_WINDOW_SECONDS', 'current_value' => '60', 'suggested_value' => '45'],
            ],
        ]);
        $audit->id = 1;

        $trade = new Trade([
            'asset' => 'BTC',
            'side' => 'YES',
        ]);

        $result = $this->formatter->formatLossAudit($audit, $trade);

        $this->assertStringContainsString('Loss Audited', $result);
        $this->assertStringContainsString('BTC YES', $result);
        $this->assertStringContainsString('MIN_CONFIDENCE_SCORE', $result);
        $this->assertStringContainsString('2', $result); // fix count
        $this->assertLessThanOrEqual(4096, strlen($result));
    }

    public function test_format_balance_alert(): void
    {
        $user = new User(['name' => 'Test']);
        $result = $this->formatter->formatBalanceAlert(18.50, 20.00, $user);

        $this->assertStringContainsString('Low Balance', $result);
        $this->assertStringContainsString('18.50', $result);
        $this->assertStringContainsString('20.00', $result);
    }

    public function test_format_error_alert(): void
    {
        $user = new User(['name' => 'Test']);
        $result = $this->formatter->formatErrorAlert('Connection timeout', 'Polymarket API', $user);

        $this->assertStringContainsString('Simulator Error', $result);
        $this->assertStringContainsString('Connection timeout', $result);
        $this->assertStringContainsString('Polymarket API', $result);
    }

    public function test_format_subscription_activated(): void
    {
        $user = new User(['name' => 'Test']);
        $result = $this->formatter->formatSubscriptionActivated($user, 'Pro', now()->addMonth());

        $this->assertStringContainsString('Subscription Activated', $result);
        $this->assertStringContainsString('Pro', $result);
    }

    public function test_format_subscription_expired(): void
    {
        $user = new User(['name' => 'Test']);
        $result = $this->formatter->formatSubscriptionExpired($user);

        $this->assertStringContainsString('Subscription Expired', $result);
        $this->assertStringContainsString('paused', $result);
    }

    public function test_format_welcome(): void
    {
        $user = new User(['name' => 'Test', 'account_id' => 'PTX-TEST123']);
        $result = $this->formatter->formatWelcome($user);

        $this->assertStringContainsString('Welcome', $result);
        $this->assertStringContainsString('PTX-TEST123', $result);
        $this->assertStringContainsString('notifications', $result);
    }

    public function test_all_formatters_under_4096_chars(): void
    {
        $user = new User(['name' => 'Test', 'account_id' => 'PTX-TEST', 'subscription_plan' => 'pro']);

        $results = [
            $this->formatter->formatBalanceAlert(10, 20, $user),
            $this->formatter->formatDrawdownAlert(-15, 30, 25, $user),
            $this->formatter->formatErrorAlert(str_repeat('x', 600), 'ctx', $user),
            $this->formatter->formatSubscriptionActivated($user, 'Pro', now()->addMonth()),
            $this->formatter->formatSubscriptionExpiring($user, 3),
            $this->formatter->formatSubscriptionExpired($user),
            $this->formatter->formatBotPaused('Daily loss limit', $user),
            $this->formatter->formatWelcome($user),
        ];

        foreach ($results as $result) {
            $this->assertLessThanOrEqual(4096, strlen($result));
        }
    }
}
