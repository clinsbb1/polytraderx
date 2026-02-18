<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

class AnnouncementTemplate
{
    public static function render(string $text, User $user): string
    {
        return strtr($text, self::variables($user));
    }

    /**
     * @return array<string, string>
     */
    public static function variables(User $user): array
    {
        $firstName = trim(explode(' ', trim((string) $user->name))[0] ?? '');
        $planName = $user->currentPlan()?->name ?? ucfirst((string) $user->subscription_plan);
        $subscriptionEnds = $user->subscription_ends_at?->format('M j, Y') ?? 'N/A';

        return [
            '{{name}}' => (string) $user->name,
            '{{first_name}}' => $firstName !== '' ? $firstName : (string) $user->name,
            '{{email}}' => (string) ($user->email ?? ''),
            '{{account_id}}' => (string) ($user->account_id ?? ''),
            '{{plan}}' => (string) $planName,
            '{{subscription_ends_at}}' => $subscriptionEnds,
            '{{dashboard_url}}' => (string) url('/dashboard'),
            '{{today}}' => now()->format('M j, Y'),
            '{{support_email}}' => 'support@polytraderx.xyz',
        ];
    }
}
