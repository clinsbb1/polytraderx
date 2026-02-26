<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class CustomBotRequest extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'contact',
        'strategy_summary',
        'markets',
        'timeframe',
        'risk_limits_json',
        'wants_ai',
        'budget_range',
        'timeline',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'risk_limits_json' => 'array',
            'wants_ai' => 'boolean',
        ];
    }
}
