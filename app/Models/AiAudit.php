<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class AiAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'trigger',
        'losing_trade_ids',
        'analysis',
        'suggested_fixes',
        'status',
        'reviewed_at',
        'review_notes',
        'applied_at',
        'created_at',
    ];

    protected $casts = [
        'losing_trade_ids' => 'array',
        'suggested_fixes' => 'array',
        'reviewed_at' => 'datetime',
        'applied_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Load the trades referenced by losing_trade_ids.
     */
    public function losingTrades(): Collection
    {
        $ids = $this->losing_trade_ids ?? [];

        return Trade::withTrashed()->whereIn('id', $ids)->get();
    }
}
