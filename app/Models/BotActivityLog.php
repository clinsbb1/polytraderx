<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class BotActivityLog extends Model
{
    use BelongsToUser;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'cycle_id',
        'event',
        'market_id',
        'asset',
        'matched_strategy',
        'action',
        'message',
        'context',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'matched_strategy' => 'boolean',
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

