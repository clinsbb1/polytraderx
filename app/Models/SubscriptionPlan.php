<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'price_usd',
        'price_crypto',
        'billing_period',
        'max_daily_trades',
        'max_concurrent_positions',
        'has_ai_muscles',
        'has_ai_brain',
        'trial_days',
        'is_active',
        'sort_order',
        'features_json',
    ];

    protected function casts(): array
    {
        return [
            'price_usd' => 'decimal:2',
            'price_crypto' => 'decimal:6',
            'max_daily_trades' => 'integer',
            'max_concurrent_positions' => 'integer',
            'has_ai_muscles' => 'boolean',
            'has_ai_brain' => 'boolean',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
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
}
