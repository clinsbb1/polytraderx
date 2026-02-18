<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::channel('simulator')->warning('Turnstile verification skipped due to missing secret or token', [
                'secret_configured' => $secret !== '',
                'token_present' => $token !== null && $token !== '',
                'ip' => $ip,
            ]);
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

            $success = (bool) $response->json('success', false);

            if (!$success) {
                Log::channel('simulator')->warning('Turnstile verification failed', [
                    'status' => $response->status(),
                    'error_codes' => $response->json('error-codes', []),
                    'hostname' => $response->json('hostname'),
                    'action' => $response->json('action'),
                    'ip' => $ip,
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Turnstile verification request failed', [
                'ip' => $ip,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
