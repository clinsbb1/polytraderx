<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'balance_usdc',
        'open_positions_value',
        'total_equity',
        'snapshot_at',
        'created_at',
    ];

    protected $casts = [
        'balance_usdc' => 'decimal:2',
        'open_positions_value' => 'decimal:2',
        'total_equity' => 'decimal:2',
        'snapshot_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
