<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'PLATFORM_NAME', 'value' => 'PolyTraderX', 'type' => 'string', 'group' => 'general', 'description' => 'Platform display name'],
            ['key' => 'SUPPORT_EMAIL', 'value' => 'support@polytraderx.com', 'type' => 'string', 'group' => 'general', 'description' => 'Support contact email'],
            ['key' => 'MAX_USERS', 'value' => '1000', 'type' => 'number', 'group' => 'general', 'description' => 'Maximum number of registered users'],
            ['key' => 'REGISTRATION_ENABLED', 'value' => 'true', 'type' => 'boolean', 'group' => 'general', 'description' => 'Allow new user registrations'],
            ['key' => 'DEFAULT_TRIAL_DAYS', 'value' => '7', 'type' => 'number', 'group' => 'general', 'description' => 'Default trial length in days'],
            ['key' => 'NOWPAYMENTS_SANDBOX_MODE', 'value' => 'true', 'type' => 'boolean', 'group' => 'payments', 'description' => 'Use NOWPayments sandbox environment'],
            ['key' => 'GOOGLE_ANALYTICS_ID', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Google Analytics tracking ID'],
            ['key' => 'MAINTENANCE_MODE', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'description' => 'Put platform in maintenance mode'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
