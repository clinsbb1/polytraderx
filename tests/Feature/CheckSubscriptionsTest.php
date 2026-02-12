<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivates_expired_trial(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:check-expired')->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    public function test_deactivates_expired_subscription(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_ends_at' => now()->subDay(),
        ]);

        $this->artisan('subscriptions:check-expired')->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertEquals('free_trial', $user->subscription_plan);
    }

    public function test_does_not_deactivate_active_subscription(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'pro',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $this->artisan('subscriptions:check-expired')->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertEquals('pro', $user->subscription_plan);
    }

    public function test_does_not_deactivate_active_trial(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(5),
        ]);

        $this->artisan('subscriptions:check-expired')->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->is_active);
    }
}
