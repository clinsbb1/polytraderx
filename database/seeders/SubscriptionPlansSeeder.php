<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // Plan 1: Free (Starter)
            [
                'slug' => 'free',
                'name' => 'Free',
                'price_usd' => 0.00,
                'yearly_price' => null,
                'billing_period' => 'monthly',
                'max_signals_per_day' => 5,
                'max_concurrent_positions' => 1,
                'max_ai_muscles_calls_per_day' => 3,
                'max_ai_brain_calls_per_day' => 0,
                'max_ai_brain_calls_per_month' => 0,
                'ai_monthly_token_cap' => 20000,
                'ai_brain_calls_per_day' => 2,
                'ai_muscles_calls_per_day' => 5,
                'ai_max_tokens_per_request' => 3500,
                'csv_export_enabled' => false,
                'strategy_health_metrics' => false,
                'telegram_enabled' => false,
                'historical_days' => 7,
                'priority_processing' => false,
                'ai_muscles_enabled' => true,
                'ai_brain_enabled' => false,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 1,
                'features_json' => json_encode([
                    '5 signals per day maximum',
                    '1 concurrent position only',
                    'AI Reflexes (rule-based)',
                    'AI Muscles (3 calls/day)',
                    'No AI Brain analysis',
                    '7-day historical data',
                    'Basic dashboard only',
                    'No Telegram notifications',
                    'No CSV exports',
                ]),
            ],

            // Plan 2: Pro
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_usd' => 39.00,
                'yearly_price' => 399.00,
                'billing_period' => 'monthly',
                'max_signals_per_day' => 200,
                'max_concurrent_positions' => 5,
                'max_ai_muscles_calls_per_day' => 100,
                'max_ai_brain_calls_per_day' => 5,
                'max_ai_brain_calls_per_month' => 50,
                'ai_monthly_token_cap' => 150000,
                'ai_brain_calls_per_day' => 10,
                'ai_muscles_calls_per_day' => 100,
                'ai_max_tokens_per_request' => 6000,
                'csv_export_enabled' => true,
                'strategy_health_metrics' => true,
                'telegram_enabled' => true,
                'historical_days' => 90,
                'priority_processing' => false,
                'ai_muscles_enabled' => true,
                'ai_brain_enabled' => true,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 2,
                'features_json' => json_encode([
                    'Unlimited strategy simulations',
                    'Full dashboard & analytics',
                    'Strategy health metrics',
                    'AI Reflexes + Muscles + Brain (capped)',
                    'Telegram notifications',
                    'CSV exports',
                    '90-day historical data',
                ]),
            ],

            // Plan 3: Advanced
            [
                'slug' => 'advanced',
                'name' => 'Advanced',
                'price_usd' => 79.00,
                'yearly_price' => 799.00,
                'billing_period' => 'monthly',
                'max_signals_per_day' => 0, // 0 = unlimited
                'max_concurrent_positions' => 10,
                'max_ai_muscles_calls_per_day' => 0, // unlimited
                'max_ai_brain_calls_per_day' => 20,
                'max_ai_brain_calls_per_month' => 200,
                'ai_monthly_token_cap' => 400000,
                'ai_brain_calls_per_day' => 25,
                'ai_muscles_calls_per_day' => 250,
                'ai_max_tokens_per_request' => 9000,
                'csv_export_enabled' => true,
                'strategy_health_metrics' => true,
                'telegram_enabled' => true,
                'historical_days' => 365,
                'priority_processing' => true,
                'ai_muscles_enabled' => true,
                'ai_brain_enabled' => true,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 3,
                'features_json' => json_encode([
                    'Everything in Pro',
                    'Unlimited signals & AI Muscles',
                    'Higher AI Brain limits',
                    'Deep audits (overfitting & regime analysis)',
                    'Priority simulation processing',
                    'Priority support',
                    '365-day historical data',
                    'Early access to future features',
                ]),
            ],

            // Plan 4: Early Bird Lifetime
            [
                'slug' => 'lifetime',
                'name' => 'Early Bird — Lifetime',
                'price_usd' => 999.00,
                'yearly_price' => null,
                'billing_period' => 'lifetime',
                'max_signals_per_day' => 0,
                'max_concurrent_positions' => 10,
                'max_ai_muscles_calls_per_day' => 0,
                'max_ai_brain_calls_per_day' => 20,
                'max_ai_brain_calls_per_month' => 200,
                'ai_monthly_token_cap' => 400000,
                'ai_brain_calls_per_day' => 25,
                'ai_muscles_calls_per_day' => 250,
                'ai_max_tokens_per_request' => 9000,
                'csv_export_enabled' => true,
                'strategy_health_metrics' => true,
                'telegram_enabled' => true,
                'historical_days' => 365,
                'priority_processing' => true,
                'ai_muscles_enabled' => true,
                'ai_brain_enabled' => true,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 4,
                'lifetime_cap' => 50,
                'lifetime_sold' => 0,
                'features_json' => json_encode([
                    'Lifetime access to Advanced features',
                    'One-time payment — no subscription',
                    'Non-refundable',
                    'Lifetime = lifetime of product, not user',
                    'No guarantee of live execution',
                ]),
            ],
        ];

        DB::transaction(function () use ($plans): void {
            // Reset current pricing structure before inserting the canonical plan set.
            SubscriptionPlan::query()->delete();

            foreach ($plans as $plan) {
                SubscriptionPlan::create($plan);
            }
        });
    }
}
