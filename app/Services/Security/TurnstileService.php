<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;

class TurnstileService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled', false);
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $secret = (string) config('services.turnstile.secret_key', '');

        if ($secret === '' || $token === null || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            return (bool) $response->json('success', false);
        } catch (\Throwable) {
            return false;
        }
    }
}
