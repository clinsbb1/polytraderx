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
            // General
            ['key' => 'PLATFORM_NAME', 'value' => 'PolyTraderX', 'type' => 'string', 'group' => 'general', 'description' => 'Platform display name'],
            ['key' => 'PLATFORM_TAGLINE', 'value' => 'AI-Powered Polymarket Trading', 'type' => 'string', 'group' => 'general', 'description' => 'Platform tagline'],
            ['key' => 'SUPPORT_EMAIL', 'value' => 'support@polytraderx.com', 'type' => 'string', 'group' => 'general', 'description' => 'Support contact email'],
            ['key' => 'MAX_USERS', 'value' => '1000', 'type' => 'number', 'group' => 'general', 'description' => 'Maximum number of registered users'],
            ['key' => 'REGISTRATION_ENABLED', 'value' => 'true', 'type' => 'boolean', 'group' => 'general', 'description' => 'Allow new user registrations'],
            ['key' => 'DEFAULT_TRIAL_DAYS', 'value' => '7', 'type' => 'number', 'group' => 'general', 'description' => 'Default trial length in days'],
            ['key' => 'GOOGLE_ANALYTICS_ID', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Google Analytics tracking ID'],
            ['key' => 'MAINTENANCE_MODE', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'description' => 'Put platform in maintenance mode'],

            // Telegram
            ['key' => 'TELEGRAM_BOT_TOKEN', 'value' => '', 'type' => 'string', 'group' => 'telegram', 'description' => 'Telegram bot token from @BotFather'],
            ['key' => 'TELEGRAM_BOT_USERNAME', 'value' => 'PolyTraderXBot', 'type' => 'string', 'group' => 'telegram', 'description' => 'Telegram bot username (without @)'],

            // AI
            ['key' => 'ANTHROPIC_API_KEY', 'value' => '', 'type' => 'string', 'group' => 'ai', 'description' => 'Anthropic API key for AI services'],
            ['key' => 'AI_BRAIN_MODEL', 'value' => 'claude-sonnet-4-5-20250929', 'type' => 'string', 'group' => 'ai', 'description' => 'Claude model for Brain tier (expensive, high quality)'],
            ['key' => 'AI_MUSCLES_MODEL', 'value' => 'claude-haiku-4-5-20251001', 'type' => 'string', 'group' => 'ai', 'description' => 'Claude model for Muscles tier (cheap, fast)'],

            // Payments
            ['key' => 'NOWPAYMENTS_API_KEY', 'value' => '', 'type' => 'string', 'group' => 'payments', 'description' => 'NOWPayments API key'],
            ['key' => 'NOWPAYMENTS_IPN_SECRET', 'value' => '', 'type' => 'string', 'group' => 'payments', 'description' => 'NOWPayments IPN callback secret'],
            ['key' => 'NOWPAYMENTS_SANDBOX_MODE', 'value' => 'true', 'type' => 'boolean', 'group' => 'payments', 'description' => 'Use NOWPayments sandbox environment'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
