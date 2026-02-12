<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trade>
 */
class TradeFactory extends Factory
{
    protected $model = Trade::class;

    public function definition(): array
    {
        $side = fake()->randomElement(['YES', 'NO']);
        $entryPrice = $side === 'YES' ? fake()->randomFloat(4, 0.90, 0.98) : fake()->randomFloat(4, 0.90, 0.98);
        $amount = fake()->randomFloat(2, 1.0, 10.0);

        return [
            'user_id' => User::factory(),
            'market_id' => '0x' . fake()->sha256(),
            'market_slug' => 'btc-15min-up-' . fake()->slug(2),
            'market_question' => 'Will BTC be higher at ' . fake()->time('g:i A') . ' UTC?',
            'asset' => fake()->randomElement(['BTC', 'ETH', 'SOL']),
            'side' => $side,
            'entry_price' => $entryPrice,
            'exit_price' => null,
            'amount' => $amount,
            'potential_payout' => round($amount / $entryPrice, 2),
            'status' => 'open',
            'confidence_score' => fake()->randomFloat(4, 0.92, 0.99),
            'decision_tier' => 'reflexes',
            'decision_reasoning' => ['test' => true],
            'external_spot_at_entry' => fake()->randomFloat(2, 60000, 100000),
            'external_spot_at_resolution' => null,
            'market_end_time' => now()->addMinutes(15),
            'entry_at' => now(),
            'resolved_at' => null,
            'pnl' => null,
            'audited' => false,
        ];
    }

    public function won(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'won',
            'exit_price' => 1.0,
            'resolved_at' => now(),
            'pnl' => round($attributes['potential_payout'] - $attributes['amount'], 2),
            'market_end_time' => now()->subMinutes(5),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'lost',
            'exit_price' => 0.0,
            'resolved_at' => now(),
            'pnl' => -1 * $attributes['amount'],
            'market_end_time' => now()->subMinutes(5),
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function expired(): static
    {
        return $this->state(['market_end_time' => now()->subMinutes(5)]);
    }

    public function forAsset(string $asset): static
    {
        return $this->state(['asset' => $asset]);
    }
}
