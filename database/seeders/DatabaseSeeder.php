<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserCredential;
use App\Services\Settings\SettingsService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Seed subscription plans
        $this->call(SubscriptionPlansSeeder::class);

        // Seed platform settings
        $this->call(PlatformSettingsSeeder::class);

        // Create superadmin user
        $superadmin = User::updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'admin@polytraderx.com')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
                'is_superadmin' => true,
                'is_active' => true,
                'onboarding_completed' => true,
                'subscription_plan' => 'pro',
                'subscription_ends_at' => now()->addYears(10),
                'timezone' => 'Africa/Lagos',
            ]
        );

        // Create superadmin credentials
        UserCredential::firstOrCreate(['user_id' => $superadmin->id]);

        // Seed superadmin's strategy params
        app(SettingsService::class)->seedUserParams($superadmin->id);

        // In local/dev, create a demo user
        if (app()->environment('local')) {
            $demo = User::updateOrCreate(
                ['email' => 'demo@polytraderx.com'],
                [
                    'name' => 'Demo User',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'onboarding_completed' => true,
                    'subscription_plan' => 'free_trial',
                    'trial_ends_at' => now()->addDays(7),
                    'timezone' => 'Africa/Lagos',
                ]
            );

            UserCredential::firstOrCreate(['user_id' => $demo->id]);
            app(SettingsService::class)->seedUserParams($demo->id);
        }
    }
}
