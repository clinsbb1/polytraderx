<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Email\LifecycleEmailService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'account_id',
        'timezone',
        'is_active',
        'is_superadmin',
        'subscription_plan',
        'billing_interval',
        'is_lifetime',
        'subscription_ends_at',
        'trial_ends_at',
        'onboarding_completed',
        'last_bot_heartbeat',
        'last_login_at',
        'avatar_url',
        'telegram_chat_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_linked_at',
        'google_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'referred_by',
        'simulation_acknowledged_at',
        'pro_trial_used_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_superadmin' => 'boolean',
            'is_lifetime' => 'boolean',
            'subscription_ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'onboarding_completed' => 'boolean',
            'last_bot_heartbeat' => 'datetime',
            'last_login_at' => 'datetime',
            'telegram_linked_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'simulation_acknowledged_at' => 'datetime',
            'pro_trial_used_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (User $user): void {
            if (empty($user->account_id)) {
                $user->account_id = 'PTX-' . strtoupper(Str::random(12));
            }
        });
    }

    // --- Relationships ---

    public function credential(): HasOne
    {
        return $this->hasOne(UserCredential::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function aiDecisions(): HasMany
    {
        return $this->hasMany(AiDecision::class);
    }

    public function aiAudits(): HasMany
    {
        return $this->hasMany(AiAudit::class);
    }

    public function balanceSnapshots(): HasMany
    {
        return $this->hasMany(BalanceSnapshot::class);
    }

    public function dailySummaries(): HasMany
    {
        return $this->hasMany(DailySummary::class);
    }

    public function strategyParams(): HasMany
    {
        return $this->hasMany(StrategyParam::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // --- Helper Methods ---

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    public function isSubscriptionActive(): bool
    {
        if ($this->is_superadmin) {
            return true;
        }

        if (!$this->is_active) {
            return false;
        }

        if ($this->is_lifetime || $this->subscription_plan === 'lifetime') {
            return true;
        }

        if (
            $this->subscription_plan === 'free'
            && SubscriptionPlan::isFreeModeEnabled()
            && !$this->isTrialExpired()
        ) {
            return true;
        }

        if ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) {
            return true;
        }

        return false;
    }

    public function isTrialExpired(): bool
    {
        if (!$this->trial_ends_at) {
            return false; // null = no trial limit (indefinite free access for downgraded users)
        }

        return $this->trial_ends_at->isPast();
    }

    public function daysLeftInTrial(): int
    {
        if (!$this->trial_ends_at || $this->trial_ends_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    public function isOnProTrial(): bool
    {
        return $this->billing_interval === 'trial'
            && $this->subscription_plan === 'pro'
            && $this->subscription_ends_at !== null
            && $this->subscription_ends_at->isFuture();
    }

    public function currentPlan(): ?SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $this->subscription_plan)->first();
    }

    public function hasTelegramLinked(): bool
    {
        return !empty($this->telegram_chat_id);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return (bool) $this->two_factor_enabled && !empty($this->two_factor_secret);
    }

    public function hasPolymarketConfigured(): bool
    {
        return $this->credential && $this->credential->hasPolymarketKeys();
    }

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        $sent = app(LifecycleEmailService::class)->sendPasswordReset($this, $resetUrl);

        if (!$sent) {
            Log::channel('simulator')->warning('Custom password reset email failed, using default notification', [
                'user_id' => $this->id,
                'email' => $this->email,
            ]);

            $this->notify(new ResetPassword($token));
        }
    }

    // --- Scopes ---

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithActiveSubscription(Builder $query): Builder
    {
        $freeModeEnabled = SubscriptionPlan::isFreeModeEnabled();

        return $query->where(function (Builder $q) use ($freeModeEnabled) {
            $q->where('subscription_ends_at', '>', now())
                ->orWhere('is_lifetime', true);

            if ($freeModeEnabled) {
                $q->orWhere(function (Builder $q2) {
                    $q2->where('subscription_plan', 'free')
                        ->where('trial_ends_at', '>', now());
                });
            }
        });
    }
}
