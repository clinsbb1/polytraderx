<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SubscriptionPlan extends Model
{
    private const FREE_MODE_CACHE_KEY = 'subscription_plans:free_mode_enabled';

    protected $fillable = [
        'slug',
        'name',
        'price_usd',
        'yearly_price',
        'price_crypto',
        'billing_period',
        'max_signals_per_day',
        'max_concurrent_positions',
        'max_ai_muscles_calls_per_day',
        'max_ai_brain_calls_per_day',
        'max_ai_brain_calls_per_month',
        'ai_monthly_token_cap',
        'ai_brain_calls_per_day',
        'ai_muscles_calls_per_day',
        'ai_max_tokens_per_request',
        'csv_export_enabled',
        'strategy_health_metrics',
        'telegram_enabled',
        'historical_days',
        'priority_processing',
        'ai_muscles_enabled',
        'ai_brain_enabled',
        'trial_days',
        'is_active',
        'sort_order',
        'lifetime_cap',
        'lifetime_sold',
        'features_json',
    ];

    protected static function booted(): void
    {
        static::saved(function (): void {
            self::flushAvailabilityCache();
        });

        static::deleted(function (): void {
            self::flushAvailabilityCache();
        });
    }

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'price_crypto' => 'decimal:6',
            'max_signals_per_day' => 'integer',
            'max_concurrent_positions' => 'integer',
            'max_ai_muscles_calls_per_day' => 'integer',
            'max_ai_brain_calls_per_day' => 'integer',
            'max_ai_brain_calls_per_month' => 'integer',
            'ai_monthly_token_cap' => 'integer',
            'ai_brain_calls_per_day' => 'integer',
            'ai_muscles_calls_per_day' => 'integer',
            'ai_max_tokens_per_request' => 'integer',
            'csv_export_enabled' => 'boolean',
            'strategy_health_metrics' => 'boolean',
            'telegram_enabled' => 'boolean',
            'historical_days' => 'integer',
            'priority_processing' => 'boolean',
            'ai_muscles_enabled' => 'boolean',
            'ai_brain_enabled' => 'boolean',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'lifetime_cap' => 'integer',
            'lifetime_sold' => 'integer',
            'features_json' => 'array',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public static function isFreeModeEnabled(): bool
    {
        try {
            if (!Schema::hasTable((new self())->getTable())) {
                return false;
            }

            return (bool) Cache::remember(
                self::FREE_MODE_CACHE_KEY,
                3600,
                fn () => self::query()
                    ->where('slug', 'free')
                    ->where('is_active', true)
                    ->exists()
            );
        } catch (\Throwable) {
            return false;
        }
    }

    public static function flushAvailabilityCache(): void
    {
        Cache::forget(self::FREE_MODE_CACHE_KEY);
    }
}
