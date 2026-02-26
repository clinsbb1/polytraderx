<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    use BelongsToUser;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'date',
        'total_trades',
        'wins',
        'losses',
        'win_rate',
        'gross_pnl',
        'net_pnl',
        'ai_cost_usd',
        'starting_balance',
        'ending_balance',
        'best_trade_id',
        'worst_trade_id',
        'created_at',
        'telegram_notified_at',
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
        'starting_balance' => 'decimal:2',
        'ending_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'telegram_notified_at' => 'datetime',
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
