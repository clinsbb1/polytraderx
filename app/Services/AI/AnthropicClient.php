<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    private const API_VERSION = '2023-06-01';
    private const MAX_RETRIES = 2;
    private const CREDIT_PAUSE_CACHE_KEY = 'ai:anthropic:insufficient_credit';
    private const CREDIT_PAUSE_SECONDS = 900;

    private string $baseUrl;

    public function __construct(private PlatformSettingsService $platformSettings)
    {
        $this->baseUrl = config('services.anthropic.base_url', 'https://api.anthropic.com/v1');
    }

    public function sendMessage(string $model, string $systemPrompt, string $userMessage, int $maxTokens = 2048): array
    {
        $creditPauseReason = Cache::get(self::CREDIT_PAUSE_CACHE_KEY);
        if (is_string($creditPauseReason) && trim($creditPauseReason) !== '') {
            throw new \RuntimeException($creditPauseReason);
        }

        $apiKey = $this->platformSettings->get('ANTHROPIC_API_KEY', '');

        if (empty($apiKey)) {
            throw new \RuntimeException('Anthropic API key not configured in platform settings');
        }

        $timeout = str_contains($model, 'haiku') ? 60 : 120;

        $payload = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ];

        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'x-api-key' => $apiKey,
                        'anthropic-version' => self::API_VERSION,
                        'content-type' => 'application/json',
                    ])
                    ->post("{$this->baseUrl}/messages", $payload);

                if ($response->successful()) {
                    Cache::forget(self::CREDIT_PAUSE_CACHE_KEY);
                    $data = $response->json();
                    $content = '';
                    foreach ($data['content'] ?? [] as $block) {
                        if (($block['type'] ?? '') === 'text') {
                            $content .= $block['text'];
                        }
                    }

                    return [
                        'content' => $content,
                        'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                        'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                        'model' => $data['model'] ?? $model,
                        'stop_reason' => $data['stop_reason'] ?? '',
                    ];
                }

                $status = $response->status();
                $errorText = $this->extractErrorMessage($response->json() ?? [], $response->body());

                if ($this->isInsufficientCredit($status, $errorText)) {
                    $pauseMessage = 'Anthropic credits depleted or billing inactive. AI calls paused temporarily.';
                    Cache::put(self::CREDIT_PAUSE_CACHE_KEY, $pauseMessage, self::CREDIT_PAUSE_SECONDS);
                    Log::channel('simulator')->warning('Anthropic insufficient credit detected; pausing AI calls', [
                        'status' => $status,
                        'error' => $errorText,
                        'pause_seconds' => self::CREDIT_PAUSE_SECONDS,
                    ]);
                    throw new \RuntimeException($pauseMessage);
                }

                if ($status === 401) {
                    Log::channel('simulator')->error('Invalid Anthropic API key in platform settings');
                    throw new \RuntimeException('Invalid Anthropic API key');
                }

                if ($status === 429) {
                    $retryAfter = (int) ($response->header('retry-after') ?: 5);
                    Log::channel('simulator')->warning('Anthropic rate limited', [
                        'retry_after' => $retryAfter,
                        'attempt' => $attempt + 1,
                    ]);
                    sleep($retryAfter);
                    continue;
                }

                if ($status === 529) {
                    Log::channel('simulator')->warning('Anthropic overloaded, retrying in 10s');
                    sleep(10);
                    continue;
                }

                Log::channel('simulator')->error('Anthropic API error', [
                    'status' => $status,
                    'body' => $response->body(),
                    'model' => $model,
                ]);
                throw new \RuntimeException("Anthropic API error ({$status}): {$response->body()}");
            } catch (\RuntimeException $e) {
                throw $e;
            } catch (\Exception $e) {
                $lastException = $e;
                Log::channel('simulator')->warning('Anthropic request exception', [
                    'attempt' => $attempt + 1,
                    'message' => $e->getMessage(),
                ]);
                if ($attempt < self::MAX_RETRIES) {
                    sleep(2);
                }
            }
        }

        throw new \RuntimeException(
            'Anthropic API failed after retries: ' . ($lastException?->getMessage() ?? 'Unknown error')
        );
    }

    public function isConfigured(): bool
    {
        $key = $this->platformSettings->get('ANTHROPIC_API_KEY', '');
        return !empty($key);
    }

    private function extractErrorMessage(array $json, string $fallbackBody): string
    {
        $message = (string) ($json['error']['message']
            ?? $json['error']['detail']
            ?? $json['message']
            ?? $fallbackBody);

        return trim($message);
    }

    private function isInsufficientCredit(int $status, string $errorText): bool
    {
        if ($status === 402) {
            return true;
        }

        $text = strtolower($errorText);
        if ($text === '') {
            return false;
        }

        if (str_contains($text, 'payment required')) {
            return true;
        }

        $mentionsCredit = str_contains($text, 'credit') || str_contains($text, 'balance') || str_contains($text, 'billing');
        $indicatesShortfall = str_contains($text, 'insufficient')
            || str_contains($text, 'depleted')
            || str_contains($text, 'too low')
            || str_contains($text, 'exhausted')
            || str_contains($text, 'no balance');

        return $mentionsCredit && $indicatesShortfall;
    }
}
