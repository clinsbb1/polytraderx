<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'date',
        'total_trades',
        'wins',
        'losses',
        'win_rate',
        'gross_pnl',
        'net_pnl',
        'ai_cost_usd',
        'best_trade_id',
        'worst_trade_id',
        'created_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_trades' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'win_rate' => 'decimal:2',
        'gross_pnl' => 'decimal:2',
        'net_pnl' => 'decimal:2',
        'ai_cost_usd' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function bestTrade(): BelongsTo
    {
        return $this->belongsTo(Trade::class, 'best_trade_id');
    }

    public function worstTrade(): BelongsTo
    {
        return $this->belongsTo(Trade::class, 'worst_trade_id');
    }
}
