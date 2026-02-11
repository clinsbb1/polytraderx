<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;

class StrategyParam extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
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
