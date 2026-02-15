<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StrategyParam;
use Illuminate\Database\Seeder;

class StrategyParamsSeeder extends Seeder
{
    public function run(): void
    {
        $params = [
            // Risk Management
            [
                'key' => 'MAX_BET_AMOUNT',
                'value' => env('SEED_MAX_BET_AMOUNT', '10'),
                'type' => 'decimal',
                'description' => 'Maximum single bet in USDC',
                'group' => 'risk',
            ],
            [
                'key' => 'MAX_BET_PERCENTAGE',
                'value' => env('SEED_MAX_BET_PERCENTAGE', '10.0'),
                'type' => 'decimal',
                'description' => 'Max bet as % of current bankroll',
                'group' => 'risk',
            ],
            [
                'key' => 'MAX_DAILY_LOSS',
                'value' => env('SEED_MAX_DAILY_LOSS', '50'),
                'type' => 'decimal',
                'description' => 'Stop all trading after this daily loss in USDC',
                'group' => 'risk',
            ],
            [
                'key' => 'MAX_DAILY_TRADES',
                'value' => env('SEED_MAX_DAILY_TRADES', '48'),
                'type' => 'number',
                'description' => 'Max trades per day',
                'group' => 'risk',
            ],
            [
                'key' => 'MAX_CONCURRENT_POSITIONS',
                'value' => env('SEED_MAX_CONCURRENT_POSITIONS', '2'),
                'type' => 'number',
                'description' => 'Max open bets at once (limited by subscription plan)',
                'group' => 'risk',
            ],

            // Trading Rules
            [
                'key' => 'SIMULATOR_ENABLED',
                'value' => env('SEED_SIMULATOR_ENABLED') !== null ? (env('SEED_SIMULATOR_ENABLED') ? 'true' : 'false') : 'false',
                'type' => 'boolean',
                'description' => 'Simulator Enabled - Master on/off switch',
                'group' => 'trading',
            ],
            [
                'key' => 'MIN_CONFIDENCE_SCORE',
                'value' => env('SEED_MIN_CONFIDENCE_SCORE', '0.92'),
                'type' => 'decimal',
                'description' => 'Minimum AI confidence to trade',
                'group' => 'trading',
            ],
            [
                'key' => 'MIN_ENTRY_PRICE_THRESHOLD',
                'value' => '0.92',
                'type' => 'decimal',
                'description' => 'Only buy "locked in" side at this price or above',
                'group' => 'trading',
            ],
            [
                'key' => 'MAX_ENTRY_PRICE_THRESHOLD',
                'value' => '0.08',
                'type' => 'decimal',
                'description' => 'Only buy cheap contrarian side at this price or below',
                'group' => 'trading',
            ],
            [
                'key' => 'ENTRY_WINDOW_SECONDS',
                'value' => '60',
                'type' => 'number',
                'description' => 'Only enter within this many seconds of market close',
                'group' => 'trading',
            ],
            [
                'key' => 'DRY_RUN',
                'value' => env('SEED_DRY_RUN') !== null ? (env('SEED_DRY_RUN') ? 'true' : 'false') : 'true',
                'type' => 'boolean',
                'description' => 'Paper trading mode — no real trades placed',
                'group' => 'trading',
            ],
            [
                'key' => 'MONITORED_ASSETS',
                'value' => 'BTC,ETH,SOL,XRP',
                'type' => 'string',
                'description' => 'Comma-separated list of monitored assets',
                'group' => 'trading',
            ],
            [
                'key' => 'MARKET_DURATIONS',
                'value' => '5min,15min',
                'type' => 'string',
                'description' => 'Which market durations to trade (5min, 15min, or both)',
                'group' => 'trading',
            ],

            // AI Parameters
            [
                'key' => 'AI_BRAIN_MODEL',
                'value' => 'claude-sonnet-4-5-20250929',
                'type' => 'string',
                'description' => 'Expensive AI model for deep analysis',
                'group' => 'ai',
            ],
            [
                'key' => 'AI_MUSCLES_MODEL',
                'value' => 'claude-haiku-4-5-20251001',
                'type' => 'string',
                'description' => 'Cheap AI model for quick scoring',
                'group' => 'ai',
            ],
            [
                'key' => 'AI_MONTHLY_BUDGET',
                'value' => '30.00',
                'type' => 'decimal',
                'description' => 'Stop AI calls if monthly spend exceeds this USD amount',
                'group' => 'ai',
            ],
            [
                'key' => 'AI_AUTO_APPLY_FIXES',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Auto-apply low-risk AI suggestions without admin review',
                'group' => 'ai',
            ],
            [
                'key' => 'MUSCLES_POLL_INTERVAL_MINUTES',
                'value' => '5',
                'type' => 'number',
                'description' => 'How often muscles tier runs (minutes)',
                'group' => 'ai',
            ],
            [
                'key' => 'SPOT_POLL_INTERVAL_SECONDS',
                'value' => '5',
                'type' => 'number',
                'description' => 'How often to check Binance in final minutes',
                'group' => 'ai',
            ],

            // Notification Preferences
            [
                'key' => 'NOTIFY_DAILY_PNL',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Send daily P&L summary via Telegram',
                'group' => 'notifications',
            ],
            [
                'key' => 'NOTIFY_BALANCE_ALERTS',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Low balance and drawdown alerts',
                'group' => 'notifications',
            ],
            [
                'key' => 'NOTIFY_ERRORS',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'API failures and bot crash alerts',
                'group' => 'notifications',
            ],
            [
                'key' => 'NOTIFY_WEEKLY_REPORT',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Weekly performance report',
                'group' => 'notifications',
            ],
            [
                'key' => 'NOTIFY_EACH_TRADE',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Notify on every individual trade',
                'group' => 'notifications',
            ],
            [
                'key' => 'NOTIFY_AI_AUDITS',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Notify when AI audit completes',
                'group' => 'notifications',
            ],
            [
                'key' => 'LOW_BALANCE_THRESHOLD',
                'value' => '20',
                'type' => 'decimal',
                'description' => 'Alert when balance drops below this USDC amount',
                'group' => 'notifications',
            ],
            [
                'key' => 'DRAWDOWN_ALERT_PERCENTAGE',
                'value' => '25',
                'type' => 'decimal',
                'description' => 'Alert when daily drawdown exceeds this percentage',
                'group' => 'notifications',
            ],
        ];

        foreach ($params as $param) {
            StrategyParam::updateOrCreate(
                ['key' => $param['key']],
                array_merge($param, [
                    'value' => (string) $param['value'],
                    'updated_by' => 'system',
                ])
            );
        }
    }
}
