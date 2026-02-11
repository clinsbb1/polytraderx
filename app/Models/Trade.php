<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Trade extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        'market_id',
        'market_slug',
        'market_question',
        'asset',
        'side',
        'entry_price',
        'exit_price',
        'amount',
        'potential_payout',
        'status',
        'confidence_score',
        'decision_tier',
        'decision_reasoning',
        'external_spot_at_entry',
        'external_spot_at_resolution',
        'market_end_time',
        'entry_at',
        'resolved_at',
        'pnl',
        'audited',
    ];

    protected $casts = [
        'entry_price' => 'decimal:4',
        'exit_price' => 'decimal:4',
        'amount' => 'decimal:2',
        'potential_payout' => 'decimal:2',
        'confidence_score' => 'decimal:4',
        'decision_reasoning' => 'array',
        'external_spot_at_entry' => 'decimal:2',
        'external_spot_at_resolution' => 'decimal:2',
        'market_end_time' => 'datetime',
        'entry_at' => 'datetime',
        'resolved_at' => 'datetime',
        'pnl' => 'decimal:2',
        'audited' => 'boolean',
    ];

    public function tradeLogs(): HasMany
    {
        return $this->hasMany(TradeLog::class);
    }

    public function aiDecisions(): HasMany
    {
        return $this->hasMany(AiDecision::class);
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->where('status', 'won');
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->where('status', 'lost');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForAsset(Builder $query, string $asset): Builder
    {
        return $query->where('asset', $asset);
    }
}
