<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trade_id',
        'event',
        'data',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }
}
