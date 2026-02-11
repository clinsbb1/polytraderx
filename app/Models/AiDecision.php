<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDecision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trade_id',
        'tier',
        'model_used',
        'prompt',
        'response',
        'tokens_input',
        'tokens_output',
        'cost_usd',
        'decision_type',
        'created_at',
    ];

    protected $casts = [
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
        'cost_usd' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function trade(): BelongsTo
    {
        return $this->belongsTo(Trade::class);
    }
}
