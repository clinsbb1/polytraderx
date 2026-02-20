<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'type',
        'is_active',
        'show_on_dashboard',
        'dashboard_until_at',
        'audience_type',
        'target_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_dashboard' => 'boolean',
            'dashboard_until_at' => 'datetime',
            'target_user_id' => 'integer',
        ];
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AnnouncementDismissal::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDashboard(Builder $query, ?int $userId = null): Builder
    {
        $query = $query->where('is_active', true)
            ->where('show_on_dashboard', true)
            ->whereNotNull('dashboard_until_at')
            ->where('dashboard_until_at', '>=', now())
            ->where(function (Builder $audience) use ($userId) {
                $audience->whereNull('audience_type')
                    ->orWhere('audience_type', 'all');

                if ($userId !== null) {
                    $audience->orWhere(function (Builder $single) use ($userId) {
                        $single->where('audience_type', 'single')
                            ->where('target_user_id', $userId);
                    });
                }
            });

        if ($userId !== null) {
            $query->whereDoesntHave('dismissals', function (Builder $dismissals) use ($userId) {
                $dismissals->where('user_id', $userId);
            });
        }

        return $query;
    }

    public function isClosed(): bool
    {
        return $this->dashboard_until_at !== null && $this->dashboard_until_at->isPast();
    }
}
