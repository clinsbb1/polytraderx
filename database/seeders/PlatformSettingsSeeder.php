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
            ['key' => 'SUPPORT_EMAIL', 'value' => 'support@polytraderx.xyz', 'type' => 'string', 'group' => 'general', 'description' => 'Support contact email'],
            ['key' => 'MAX_USERS', 'value' => '1000', 'type' => 'number', 'group' => 'general', 'description' => 'Maximum number of registered users'],
            ['key' => 'REGISTRATION_ENABLED', 'value' => 'true', 'type' => 'boolean', 'group' => 'general', 'description' => 'Allow new user registrations'],
            ['key' => 'DEFAULT_TRIAL_DAYS', 'value' => '7', 'type' => 'number', 'group' => 'general', 'description' => 'Default trial length in days'],
            ['key' => 'GOOGLE_ANALYTICS_ID', 'value' => '', 'type' => 'string', 'group' => 'general', 'description' => 'Google Analytics tracking ID'],
            ['key' => 'MAINTENANCE_MODE', 'value' => 'false', 'type' => 'boolean', 'group' => 'general', 'description' => 'Put platform in maintenance mode'],

            // Telegram
            ['key' => 'TELEGRAM_BOT_TOKEN', 'value' => '', 'type' => 'string', 'group' => 'telegram', 'description' => 'Telegram bot token from @BotFather'],
            ['key' => 'TELEGRAM_BOT_USERNAME', 'value' => 'PolyTraderXBot', 'type' => 'string', 'group' => 'telegram', 'description' => 'Telegram bot username (without @)'],
            ['key' => 'TELEGRAM_WEBHOOK_SECRET', 'value' => '', 'type' => 'string', 'group' => 'telegram', 'description' => 'Secret token used to verify Telegram webhook requests'],

            // AI
            ['key' => 'ANTHROPIC_API_KEY', 'value' => '', 'type' => 'string', 'group' => 'ai', 'description' => 'Anthropic API key for AI services'],
            ['key' => 'AI_BRAIN_MODEL', 'value' => 'claude-sonnet-4-5-20250929', 'type' => 'string', 'group' => 'ai', 'description' => 'Claude model for Brain tier (expensive, high quality)'],
            ['key' => 'AI_MUSCLES_MODEL', 'value' => 'claude-haiku-4-5-20251001', 'type' => 'string', 'group' => 'ai', 'description' => 'Claude model for Muscles tier (cheap, fast)'],
            ['key' => 'AI_MONTHLY_BUDGET', 'value' => '100.00', 'type' => 'decimal', 'group' => 'ai', 'description' => 'Platform-wide monthly AI spend cap in USD'],
            ['key' => 'AI_PRE_ANALYSIS_ENABLED', 'value' => 'false', 'type' => 'boolean', 'group' => 'ai', 'description' => 'Enable background AI pre-analysis (costly at scale)'],
            ['key' => 'AI_PRE_ANALYSIS_MAX_CANDIDATES', 'value' => '3', 'type' => 'number', 'group' => 'ai', 'description' => 'Max markets per cycle/user for AI pre-analysis'],
            ['key' => 'AI_MUSCLES_CACHE_TTL_SECONDS', 'value' => '900', 'type' => 'number', 'group' => 'ai', 'description' => 'Cache lifetime for Muscles results per user/market'],
            ['key' => 'AI_MUSCLES_FAILURE_COOLDOWN_SECONDS', 'value' => '300', 'type' => 'number', 'group' => 'ai', 'description' => 'Cooldown after failed Muscles response before retry'],
            ['key' => 'AI_MUSCLES_MAX_PROMPT_TOKENS_HARD_CAP', 'value' => '1500', 'type' => 'number', 'group' => 'ai', 'description' => 'Hard cap on Muscles prompt tokens per request'],
            ['key' => 'AI_MUSCLES_MAX_COMPLETION_TOKENS', 'value' => '256', 'type' => 'number', 'group' => 'ai', 'description' => 'Hard cap on Muscles completion tokens per request'],
            ['key' => 'AI_MUSCLES_ENFORCE_CHEAP_MODEL', 'value' => 'true', 'type' => 'boolean', 'group' => 'ai', 'description' => 'Force Muscles tier to Haiku-like cheap model to control cost'],
            ['key' => 'AI_AUDIT_RECHARGED_AT', 'value' => '', 'type' => 'string', 'group' => 'ai', 'description' => 'Loss audits run only for losses resolved at/after this timestamp. Empty = skip.'],

            // Payments
            ['key' => 'NOWPAYMENTS_API_KEY', 'value' => '', 'type' => 'string', 'group' => 'payments', 'description' => 'NOWPayments API key'],
            ['key' => 'NOWPAYMENTS_IPN_SECRET', 'value' => '', 'type' => 'string', 'group' => 'payments', 'description' => 'NOWPayments IPN callback secret'],
            ['key' => 'NOWPAYMENTS_SANDBOX_MODE', 'value' => 'true', 'type' => 'boolean', 'group' => 'payments', 'description' => 'Use NOWPayments sandbox environment'],

            // Features
            ['key' => 'FEATURE_LIVE_TRADING', 'value' => 'false', 'type' => 'boolean', 'group' => 'features', 'description' => 'Enable live trading with real Polymarket API calls (simulation-only when false)'],
        ];

        foreach ($settings as $setting) {
            $record = PlatformSetting::firstOrNew(['key' => $setting['key']]);

            // Never overwrite existing configured values (API keys, secrets, toggles, etc.).
            if (!$record->exists) {
                $record->value = $setting['value'];
            }

            // Keep metadata in sync with code defaults.
            $record->type = $setting['type'];
            $record->group = $setting['group'];
            $record->description = $setting['description'];
            $record->save();
        }
    }
}
