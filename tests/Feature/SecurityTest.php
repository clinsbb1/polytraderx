<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createSubscribedUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
            'is_superadmin' => false,
            'onboarding_completed' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(7),
        ], $overrides));
    }

    public function test_user_cannot_access_other_users_trade(): void
    {
        $userA = $this->createSubscribedUser();
        $userB = $this->createSubscribedUser();

        $trade = Trade::factory()->for($userA)->create();

        $response = $this->actingAs($userB)->get("/trades/{$trade->id}");

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = $this->createSubscribedUser(['is_superadmin' => false]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_webhook_routes_dont_require_csrf(): void
    {
        $response = $this->postJson('/api/webhooks/nowpayments', [
            'payment_id' => 'test',
        ]);

        // Should not be 419 (CSRF token mismatch)
        $this->assertNotEquals(419, $response->getStatusCode());
    }

    public function test_telegram_webhook_requires_valid_secret(): void
    {
        PlatformSetting::create([
            'key' => 'TELEGRAM_WEBHOOK_SECRET',
            'value' => 'test-webhook-secret',
            'type' => 'string',
            'group' => 'telegram',
            'description' => 'test',
        ]);

        $unauthorized = $this->postJson('/api/webhooks/telegram', ['update_id' => 1]);
        $unauthorized->assertStatus(401);

        $authorized = $this->withHeaders([
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ])->postJson('/api/webhooks/telegram', ['update_id' => 2]);

        $authorized->assertOk()->assertJson(['ok' => true]);
    }

    public function test_expired_trial_redirects_to_subscription(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'onboarding_completed' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/subscription');
    }
}
