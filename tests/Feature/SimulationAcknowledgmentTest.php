<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationAcknowledgmentTest extends TestCase
{
    use RefreshDatabase;

    protected function createUser(bool $acknowledged = false): User
    {
        $user = User::factory()->create([
            'is_active' => true,
            'subscription_plan' => 'free_trial',
            'trial_ends_at' => now()->addDays(7),
        ]);

        if ($acknowledged) {
            $user->simulation_acknowledged_at = now();
            $user->save();
        }

        return $user;
    }

    /** @test */
    public function new_users_are_redirected_to_acknowledgment_page(): void
    {
        $user = $this->createUser(acknowledged: false);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('simulation.acknowledge'));
    }

    /** @test */
    public function acknowledged_users_can_access_the_app(): void
    {
        $user = $this->createUser(acknowledged: true);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function acknowledgment_page_displays_correctly(): void
    {
        $user = $this->createUser(acknowledged: false);

        $response = $this->actingAs($user)->get(route('simulation.acknowledge'));

        $response->assertStatus(200);
        $response->assertSee('Simulation Platform Agreement');
        $response->assertSee('No Real Trading');
        $response->assertSee('No Real Money at Risk');
        $response->assertSee('Educational Purpose');
        $response->assertSee('No Private Keys Required');
        $response->assertSee('Simulated Performance ≠ Real Results');
        $response->assertSee('I understand and acknowledge that PolyTraderX is a simulation platform');
    }

    /** @test */
    public function accepting_acknowledgment_sets_timestamp_and_redirects(): void
    {
        $user = $this->createUser(acknowledged: false);

        $this->assertNull($user->fresh()->simulation_acknowledged_at);

        $response = $this->actingAs($user)->post(route('simulation.accept'), [
            'acknowledge' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Thank you for acknowledging. Welcome to PolyTraderX!');

        $user->refresh();
        $this->assertNotNull($user->simulation_acknowledged_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->simulation_acknowledged_at);
    }

    /** @test */
    public function cannot_access_main_app_without_acknowledging(): void
    {
        $user = $this->createUser(acknowledged: false);

        $routes = [
            '/dashboard',
            '/trades',
            '/audits',
            '/strategy',
            '/balance',
            '/logs',
            '/ai-costs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertRedirect(route('simulation.acknowledge'));
        }
    }

    /** @test */
    public function cannot_access_settings_without_acknowledging(): void
    {
        $user = $this->createUser(acknowledged: false);

        $routes = [
            '/settings/credentials',
            '/settings/profile',
            '/settings/notifications',
            '/settings/telegram',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertRedirect(route('simulation.acknowledge'));
        }
    }

    /** @test */
    public function cannot_access_subscription_page_without_acknowledging(): void
    {
        $user = $this->createUser(acknowledged: false);

        $response = $this->actingAs($user)->get('/subscription');

        $response->assertRedirect(route('simulation.acknowledge'));
    }

    /** @test */
    public function cannot_access_admin_panel_without_acknowledging(): void
    {
        $user = $this->createUser(acknowledged: false);
        $user->is_superadmin = true;
        $user->save();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertRedirect(route('simulation.acknowledge'));
    }

    /** @test */
    public function checkbox_must_be_checked_to_submit(): void
    {
        $user = $this->createUser(acknowledged: false);

        $response = $this->actingAs($user)->post(route('simulation.accept'), [
            'acknowledge' => '0', // Not checked
        ]);

        $response->assertSessionHasErrors('acknowledge');

        $user->refresh();
        $this->assertNull($user->simulation_acknowledged_at);
    }

    /** @test */
    public function acknowledged_users_are_not_redirected_from_other_pages(): void
    {
        $user = $this->createUser(acknowledged: true);

        $routes = [
            '/settings/credentials',
            '/subscription',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $response->assertStatus(200);
            $response->assertDontSee('Simulation Platform Agreement');
        }
    }

    /** @test */
    public function guest_users_are_not_redirected_to_acknowledgment(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertDontSee('Simulation Platform Agreement');
    }

    /** @test */
    public function acknowledgment_page_is_accessible_to_unauthenticated_users_who_are_logged_in(): void
    {
        $user = $this->createUser(acknowledged: false);

        // User on acknowledgment page can access it
        $response = $this->actingAs($user)->get(route('simulation.acknowledge'));
        $response->assertStatus(200);

        // User can submit the acknowledgment
        $response = $this->actingAs($user)->post(route('simulation.accept'), [
            'acknowledge' => '1',
        ]);
        $response->assertRedirect(route('dashboard'));
    }
}
