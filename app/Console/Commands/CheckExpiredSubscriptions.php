<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Deactivate users with expired subscriptions or trials';

    public function handle(): int
    {
        $expiredTrials = User::where('subscription_plan', 'free_trial')
            ->where('is_active', true)
            ->where('trial_ends_at', '<', now())
            ->get();

        foreach ($expiredTrials as $user) {
            $user->update(['is_active' => false]);
            Log::channel('bot')->info("Trial expired for user {$user->id} ({$user->email})");
        }

        $expiredSubscriptions = User::where('subscription_plan', '!=', 'free_trial')
            ->where('is_active', true)
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->get();

        foreach ($expiredSubscriptions as $user) {
            $user->update([
                'is_active' => false,
                'subscription_plan' => 'free_trial',
                'subscription_ends_at' => null,
            ]);
            Log::channel('bot')->info("Subscription expired for user {$user->id} ({$user->email})");
        }

        $total = $expiredTrials->count() + $expiredSubscriptions->count();
        $this->info("Deactivated {$total} expired users ({$expiredTrials->count()} trials, {$expiredSubscriptions->count()} subscriptions).");

        return Command::SUCCESS;
    }
}
