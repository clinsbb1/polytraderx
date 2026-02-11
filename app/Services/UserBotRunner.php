<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UserBotRunner
{
    public function getActiveUsers(): Collection
    {
        return User::where('is_active', true)
            ->where('onboarding_completed', true)
            ->where(function ($query) {
                $query->where('subscription_plan', '!=', 'free_trial')
                    ->orWhere('trial_ends_at', '>', now());
            })
            ->whereHas('credential', function ($query) {
                $query->whereNotNull('polymarket_api_key')
                    ->whereNotNull('polymarket_api_secret');
            })
            ->get();
    }

    public function runForEachUser(callable $callback): array
    {
        $users = $this->getActiveUsers();
        $results = [];

        foreach ($users as $user) {
            try {
                $results[$user->id] = [
                    'status' => 'success',
                    'result' => $callback($user),
                ];

                $user->update(['last_bot_heartbeat' => now()]);
            } catch (\Exception $e) {
                Log::channel('bot')->error("Bot run failed for user {$user->id}", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $results[$user->id] = [
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
