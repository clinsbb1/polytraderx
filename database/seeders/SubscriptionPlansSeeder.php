<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'free_trial',
                'name' => 'Free Trial',
                'price_usd' => '0.00',
                'billing_period' => 'monthly',
                'max_daily_trades' => 10,
                'max_concurrent_positions' => 1,
                'has_ai_muscles' => true,
                'has_ai_brain' => false,
                'trial_days' => 7,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'basic',
                'name' => 'Basic',
                'price_usd' => '29.00',
                'billing_period' => 'monthly',
                'max_daily_trades' => 50,
                'max_concurrent_positions' => 3,
                'has_ai_muscles' => true,
                'has_ai_brain' => false,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_usd' => '79.00',
                'billing_period' => 'monthly',
                'max_daily_trades' => 0,
                'max_concurrent_positions' => 0,
                'has_ai_muscles' => true,
                'has_ai_brain' => true,
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 3,
                'features_json' => ['Priority support', 'Custom strategy parameters'],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
