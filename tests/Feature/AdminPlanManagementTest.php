<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_includes_hidden_false_value_for_active_checkbox(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'simulation_acknowledged_at' => now(),
        ]);

        $plan = SubscriptionPlan::create([
            'slug' => 'free',
            'name' => 'Free',
            'price_usd' => 0,
            'billing_period' => 'monthly',
            'max_signals_per_day' => 5,
            'max_concurrent_positions' => 1,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($superadmin)->get("/admin/plans/{$plan->id}/edit");

        $response->assertOk();
        $response->assertSee('name="is_active" value="0"', false);
    }

    public function test_superadmin_can_set_plan_inactive(): void
    {
        $superadmin = User::factory()->create([
            'is_superadmin' => true,
            'simulation_acknowledged_at' => now(),
        ]);

        $plan = SubscriptionPlan::create([
            'slug' => 'free',
            'name' => 'Free',
            'price_usd' => 0,
            'billing_period' => 'monthly',
            'max_signals_per_day' => 5,
            'max_concurrent_positions' => 1,
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($superadmin)->put("/admin/plans/{$plan->id}", [
            'slug' => 'free',
            'name' => 'Free',
            'price_usd' => 0,
            'billing_period' => 'monthly',
            'max_signals_per_day' => 5,
            'max_concurrent_positions' => 1,
            'trial_days' => 0,
            'sort_order' => 1,
            'is_active' => '0',
        ]);

        $response->assertRedirect('/admin/plans');

        $plan->refresh();
        $this->assertFalse($plan->is_active);
    }
}

