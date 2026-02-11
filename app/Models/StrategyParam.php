<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StrategyParam extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
        'updated_by',
        'previous_value',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
