<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicClient
{
    private const API_VERSION = '2023-06-01';
    private const MAX_RETRIES = 2;

    private string $baseUrl;

    public function __construct(private PlatformSettingsService $platformSettings)
    {
        $this->baseUrl = config('services.anthropic.base_url', 'https://api.anthropic.com/v1');
    }

    public function sendMessage(string $model, string $systemPrompt, string $userMessage, int $maxTokens = 2048): array
    {
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
}
