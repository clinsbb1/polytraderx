<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Eip712SignerService
{
    public function __construct(private PlatformSettingsService $platformSettings) {}

    public function signOrder(User $user, array $intent): array
    {
        $credential = $user->credential;

        if (!$credential || !$credential->hasSigningKey()) {
            throw new \RuntimeException('Missing Polymarket private key required for EIP-712 order signing.');
        }

        $signerUrl = rtrim((string) $this->platformSettings->get('POLYMARKET_SIGNER_URL', ''), '/');
        if ($signerUrl === '') {
            throw new \RuntimeException('POLYMARKET_SIGNER_URL is not configured.');
        }

        $timeout = max(2, (int) $this->platformSettings->get('POLYMARKET_SIGNER_TIMEOUT_SECONDS', 10));
        $apiKey = trim((string) $this->platformSettings->get('POLYMARKET_SIGNER_API_KEY', ''));

        $request = Http::timeout($timeout)
            ->acceptJson()
            ->asJson();

        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        $response = $request->post("{$signerUrl}/v1/polymarket/sign-order", [
            'wallet_address' => $credential->polymarket_wallet_address,
            'private_key' => $credential->polymarket_private_key,
            'order_intent' => $intent,
        ]);

        return $this->parseSignerResponse($response);
    }

    private function parseSignerResponse(Response $response): array
    {
        if (!$response->successful()) {
            throw new \RuntimeException(
                sprintf('EIP-712 signer failed (%d): %s', $response->status(), $response->body())
            );
        }

        $payload = $response->json();
        $signedOrderPayload = $payload['signed_order_payload'] ?? null;

        if (!is_array($signedOrderPayload)) {
            throw new \RuntimeException('Signer response missing signed_order_payload.');
        }

        return $signedOrderPayload;
    }
}

