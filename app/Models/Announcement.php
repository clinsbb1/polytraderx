<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'is_active',
        'show_on_dashboard',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_dashboard' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDashboard(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('show_on_dashboard', true);
    }
}
