<?php

declare(strict_types=1);

namespace App\Services\Polymarket;

use App\Exceptions\PolymarketApiException;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PolymarketClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiSecret;
    private string $passphrase;
    private string $walletAddress;
    private int $userId;

    private const MAX_RETRIES = 3;
    private const RETRY_BACKOFF = [1, 2, 4];
    private const RATE_LIMIT_WAIT = 5;

    public function __construct(User $user)
    {
        $creds = $user->credential;

        if (!$creds || !$creds->hasPolymarketKeys()) {
            throw new \RuntimeException("User {$user->account_id} has no Polymarket credentials configured");
        }

        $this->apiKey = $creds->polymarket_api_key;
        $this->apiSecret = $creds->polymarket_api_secret;
        $this->passphrase = $creds->polymarket_api_passphrase;
        $this->walletAddress = $creds->polymarket_wallet_address;
        $this->userId = $user->id;
        $this->baseUrl = config('services.polymarket.base_url', 'https://clob.polymarket.com');
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getWalletAddress(): string
    {
        return $this->walletAddress;
    }

    public function get(string $path, array $query = []): array
    {
        $fullPath = $path;
        if (!empty($query)) {
            $fullPath .= '?' . http_build_query($query);
        }

        return $this->request('GET', $fullPath);
    }

    public function post(string $path, array $data = []): array
    {
        $body = !empty($data) ? json_encode($data) : null;

        return $this->request('POST', $path, $body);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    public function testConnection(): bool
    {
        try {
            $this->get('/time');
            return true;
        } catch (\Exception $e) {
            Log::channel('bot')->warning('Polymarket connection test failed', [
                'user_id' => $this->userId,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function signRequest(string $method, string $path, ?string $body = null, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? (int) (microtime(true) * 1000);
        $message = $timestamp . strtoupper($method) . $path . ($body ?? '');

        $signature = base64_encode(
            hash_hmac('sha256', $message, base64_decode($this->apiSecret), true)
        );

        return [
            'POLY-ADDRESS' => $this->walletAddress,
            'POLY-SIGNATURE' => $signature,
            'POLY-TIMESTAMP' => (string) $timestamp,
            'POLY-API-KEY' => $this->apiKey,
            'POLY-PASSPHRASE' => $this->passphrase,
        ];
    }

    private function request(string $method, string $path, ?string $body = null): array
    {
        $lastException = null;

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            try {
                $headers = $this->signRequest($method, $path, $body);
                $url = $this->baseUrl . $path;

                $request = Http::timeout(15)
                    ->withHeaders($headers)
                    ->withHeaders(['Content-Type' => 'application/json']);

                $response = match (strtoupper($method)) {
                    'GET' => $request->get($url),
                    'POST' => $request->withBody($body ?? '', 'application/json')->post($url),
                    'DELETE' => $request->delete($url),
                    default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
                };

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                $status = $response->status();

                if ($status === 429) {
                    $retryAfter = (int) ($response->header('Retry-After') ?: self::RATE_LIMIT_WAIT);
                    Log::channel('bot')->warning('Polymarket rate limited', [
                        'user_id' => $this->userId,
                        'attempt' => $attempt + 1,
                        'retry_after' => $retryAfter,
                    ]);
                    sleep($retryAfter);
                    continue;
                }

                if ($status >= 500) {
                    Log::channel('bot')->warning('Polymarket server error', [
                        'user_id' => $this->userId,
                        'status' => $status,
                        'path' => $path,
                        'attempt' => $attempt + 1,
                        'body' => $response->body(),
                    ]);
                    if ($attempt < self::MAX_RETRIES - 1) {
                        sleep(self::RETRY_BACKOFF[$attempt]);
                        continue;
                    }
                }

                // 4xx (except 429) — don't retry
                $errorBody = $response->body();
                Log::channel('bot')->error('Polymarket API error', [
                    'user_id' => $this->userId,
                    'method' => $method,
                    'path' => $path,
                    'status' => $status,
                    'body' => $errorBody,
                ]);

                throw new PolymarketApiException("Polymarket API error ({$status}): {$errorBody}", $status, $errorBody);
            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::channel('bot')->warning('Polymarket request exception', [
                    'user_id' => $this->userId,
                    'path' => $path,
                    'attempt' => $attempt + 1,
                    'message' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES - 1) {
                    sleep(self::RETRY_BACKOFF[$attempt]);
                }
            }
        }

        throw new PolymarketApiException(
            "Polymarket API request failed after " . self::MAX_RETRIES . " attempts: " . ($lastException?->getMessage() ?? 'Unknown error'),
            previous: $lastException,
        );
    }
}
