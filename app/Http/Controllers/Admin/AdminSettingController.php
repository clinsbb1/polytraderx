<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Polymarket\MarketService;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AdminSettingController extends Controller
{
    private const HIDDEN_KEYS = [
        'POLYMARKET_SIGNER_URL',
        'POLYMARKET_SIGNER_API_KEY',
        'POLYMARKET_SIGNER_TIMEOUT_SECONDS',
    ];

    public function __construct(private PlatformSettingsService $platformSettings) {}

    public function index(): View
    {
        $requiredDefaults = [
            [
                'key' => 'TELEGRAM_WEBHOOK_SECRET',
                'value' => '',
                'type' => 'string',
                'group' => 'telegram',
                'description' => 'Secret token used to verify Telegram webhook requests',
            ],
            [
                'key' => 'AI_PRE_ANALYSIS_ENABLED',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Enable background AI pre-analysis (costly at scale)',
            ],
            [
                'key' => 'AI_PRE_ANALYSIS_MAX_CANDIDATES',
                'value' => '3',
                'type' => 'number',
                'group' => 'ai',
                'description' => 'Max markets per cycle/user for AI pre-analysis',
            ],
            [
                'key' => 'AI_MUSCLES_CACHE_TTL_SECONDS',
                'value' => '900',
                'type' => 'number',
                'group' => 'ai',
                'description' => 'Cache lifetime for Muscles results per user/market',
            ],
            [
                'key' => 'AI_MUSCLES_FAILURE_COOLDOWN_SECONDS',
                'value' => '300',
                'type' => 'number',
                'group' => 'ai',
                'description' => 'Cooldown after failed Muscles response before retry',
            ],
            [
                'key' => 'AI_MUSCLES_MAX_PROMPT_TOKENS_HARD_CAP',
                'value' => '1500',
                'type' => 'number',
                'group' => 'ai',
                'description' => 'Hard cap on Muscles prompt tokens per request',
            ],
            [
                'key' => 'AI_MUSCLES_MAX_COMPLETION_TOKENS',
                'value' => '256',
                'type' => 'number',
                'group' => 'ai',
                'description' => 'Hard cap on Muscles completion tokens per request',
            ],
            [
                'key' => 'AI_MUSCLES_ENFORCE_CHEAP_MODEL',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Force Muscles tier to Haiku-like cheap model to control cost',
            ],
            [
                'key' => 'AI_AUDIT_RECHARGED_AT',
                'value' => '',
                'type' => 'string',
                'group' => 'ai',
                'description' => 'Loss audits run only for losses resolved at/after this timestamp. Empty = skip.',
            ],
        ];

        foreach ($requiredDefaults as $setting) {
            PlatformSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        $settings = PlatformSetting::whereNotIn('key', self::HIDDEN_KEYS)
            ->orderBy('group')
            ->orderBy('key')
            ->get();
        $groups = $settings->groupBy('group');
        $aiAuditRechargedAt = trim((string) $this->platformSettings->get('AI_AUDIT_RECHARGED_AT', ''));
        $aiAllPaused = $this->platformSettings->getBool('AI_ALL_PAUSED', false);

        return view('admin.settings.index', compact('groups', 'aiAuditRechargedAt', 'aiAllPaused'));
    }

    public function update(Request $request): RedirectResponse
    {
        $submitted = $request->input('settings', []);
        $allSettings = PlatformSetting::query()
            ->whereNotIn('key', self::HIDDEN_KEYS)
            ->select('key', 'type')
            ->get();

        foreach ($allSettings as $setting) {
            if ($setting->type === 'boolean') {
                $value = array_key_exists($setting->key, $submitted) ? $submitted[$setting->key] : 'false';
                $this->platformSettings->set($setting->key, $value);
                continue;
            }

            if (array_key_exists($setting->key, $submitted)) {
                $this->platformSettings->set($setting->key, $submitted[$setting->key]);
            }
        }

        return back()->with('success', 'Platform settings updated.');
    }

    public function markAiRechargedNow(): RedirectResponse
    {
        PlatformSetting::firstOrCreate(
            ['key' => 'AI_AUDIT_RECHARGED_AT'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'ai',
                'description' => 'Loss audits run only for losses resolved at/after this timestamp. Empty = skip.',
            ]
        );

        PlatformSetting::firstOrCreate(
            ['key' => 'AI_ALL_PAUSED'],
            [
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Emergency kill switch. Pause all Brain + Muscles AI calls globally.',
            ]
        );

        $timestamp = now()->toDateTimeString();
        $this->platformSettings->set('AI_AUDIT_RECHARGED_AT', $timestamp);
        $this->platformSettings->set('AI_ALL_PAUSED', 'false');

        return back()->with('success', "AI resumed. Recharge marker set to {$timestamp}. Loss audits will now run for new losses.");
    }

    public function pauseAi(): RedirectResponse
    {
        PlatformSetting::firstOrCreate(
            ['key' => 'AI_ALL_PAUSED'],
            [
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Emergency kill switch. Pause all Brain + Muscles AI calls globally.',
            ]
        );

        $this->platformSettings->set('AI_ALL_PAUSED', 'true');

        return back()->with('success', 'AI paused. No Brain or Muscles calls will be made until you click "Mark AI Recharged Now".');
    }

    public function telegramDiagnostics(): View
    {
        $report = $this->runTelegramDiagnostics();

        return view('admin.settings.telegram-diagnostics', compact('report'));
    }

    public function serviceDiagnostics(MarketService $marketService): View
    {
        $telegram = $this->runTelegramDiagnostics();
        $polymarket = $this->runPolymarketPublicDiagnostics($marketService);
        $anthropic = $this->runAnthropicDiagnostics();
        $turnstile = $this->runTurnstileDiagnostics();

        return view('admin.settings.diagnostics', [
            'telegram' => $telegram,
            'polymarket' => $polymarket,
            'anthropic' => $anthropic,
            'turnstile' => $turnstile,
            'checkedAt' => now(),
        ]);
    }

    private function runTelegramDiagnostics(): array
    {
        $botToken = trim((string) $this->platformSettings->get('TELEGRAM_BOT_TOKEN', ''));
        $botUsername = trim((string) $this->platformSettings->get('TELEGRAM_BOT_USERNAME', ''));
        $secret = trim((string) $this->platformSettings->get('TELEGRAM_WEBHOOK_SECRET', ''));
        $expectedWebhookUrl = url('/api/webhooks/telegram');

        $report = [
            'bot_token_configured' => $botToken !== '',
            'bot_username' => $botUsername,
            'webhook_secret_configured' => $secret !== '',
            'expected_webhook_url' => $expectedWebhookUrl,
            'get_me_ok' => null,
            'get_me_username' => null,
            'get_me_error' => null,
            'get_webhook_info_ok' => null,
            'webhook_url' => null,
            'pending_update_count' => null,
            'last_error_date' => null,
            'last_error_message' => null,
            'ip_address' => null,
            'max_connections' => null,
            'http_error' => null,
        ];

        if ($botToken === '') {
            return $report;
        }

        try {
            $base = 'https://api.telegram.org/bot' . $botToken;

            $me = Http::timeout(10)->get($base . '/getMe')->json();
            $report['get_me_ok'] = (bool) ($me['ok'] ?? false);
            $report['get_me_username'] = $me['result']['username'] ?? null;
            $report['get_me_error'] = $me['description'] ?? null;

            $info = Http::timeout(10)->get($base . '/getWebhookInfo')->json();
            $report['get_webhook_info_ok'] = (bool) ($info['ok'] ?? false);

            if (($info['ok'] ?? false) && isset($info['result']) && is_array($info['result'])) {
                $result = $info['result'];
                $report['webhook_url'] = $result['url'] ?? null;
                $report['pending_update_count'] = $result['pending_update_count'] ?? null;
                $report['last_error_date'] = $result['last_error_date'] ?? null;
                $report['last_error_message'] = $result['last_error_message'] ?? null;
                $report['ip_address'] = $result['ip_address'] ?? null;
                $report['max_connections'] = $result['max_connections'] ?? null;
            } elseif (isset($info['description'])) {
                $report['last_error_message'] = $info['description'];
            }
        } catch (\Throwable $e) {
            $report['http_error'] = $e->getMessage();
        }

        return $report;
    }

    private function runPolymarketPublicDiagnostics(MarketService $marketService): array
    {
        $report = [
            'base_url' => config('services.polymarket.base_url', 'https://clob.polymarket.com'),
            'gamma_url' => config('services.polymarket.gamma_url', 'https://gamma-api.polymarket.com'),
            'time_ok' => null,
            'time_payload' => null,
            'markets_ok' => null,
            'markets_count' => null,
            'gamma_markets_ok' => null,
            'gamma_markets_count' => null,
            'strict_source' => null,
            'strict_raw_count' => null,
            'strict_normalized_count' => null,
            'strict_rejected_count' => null,
            'strict_duration_breakdown' => [],
            'strict_asset_breakdown' => [],
            'strict_rejection_breakdown' => [],
            'strict_accepted_samples' => [],
            'strict_rejected_samples' => [],
            'strict_gamma_status' => null,
            'strict_clob_status' => null,
            'strict_http_error' => null,
            'http_error' => null,
        ];

        try {
            $base = rtrim((string) $report['base_url'], '/');
            $gammaBase = rtrim((string) $report['gamma_url'], '/');
            $timeResponse = Http::timeout(10)->get($base . '/time');
            $report['time_ok'] = $timeResponse->successful();
            $report['time_payload'] = $timeResponse->successful() ? $timeResponse->json() : $timeResponse->body();

            $marketsResponse = Http::timeout(10)->get($base . '/markets', ['active' => 'true']);
            $report['markets_ok'] = $marketsResponse->successful();
            if ($marketsResponse->successful()) {
                $payload = $marketsResponse->json();
                $markets = $payload['data'] ?? $payload;
                $report['markets_count'] = is_array($markets) ? count($markets) : 0;
            }

            $gammaMarketsResponse = Http::timeout(10)->get($gammaBase . '/markets', [
                'active' => true,
                'closed' => false,
                'archived' => false,
                'limit' => 1000,
            ]);
            $report['gamma_markets_ok'] = $gammaMarketsResponse->successful();
            if ($gammaMarketsResponse->successful()) {
                $payload = $gammaMarketsResponse->json();
                $markets = $payload['data'] ?? $payload;
                $report['gamma_markets_count'] = is_array($markets) ? count($markets) : 0;
            }
        } catch (\Throwable $e) {
            $report['http_error'] = $e->getMessage();
        }

        $strict = $marketService->diagnoseActiveCryptoMarkets(sampleLimit: 5);
        $report['strict_source'] = $strict['source'] ?? null;
        $report['strict_raw_count'] = $strict['raw_count'] ?? null;
        $report['strict_normalized_count'] = $strict['normalized_count'] ?? null;
        $report['strict_rejected_count'] = $strict['rejected_count'] ?? null;
        $report['strict_duration_breakdown'] = $strict['duration_breakdown'] ?? [];
        $report['strict_asset_breakdown'] = $strict['asset_breakdown'] ?? [];
        $report['strict_rejection_breakdown'] = $strict['rejection_breakdown'] ?? [];
        $report['strict_accepted_samples'] = $strict['accepted_samples'] ?? [];
        $report['strict_rejected_samples'] = $strict['rejected_samples'] ?? [];
        $report['strict_gamma_status'] = $strict['gamma_status'] ?? null;
        $report['strict_clob_status'] = $strict['clob_status'] ?? null;
        $report['strict_http_error'] = $strict['http_error'] ?? null;

        return $report;
    }

    private function runAnthropicDiagnostics(): array
    {
        $apiKey = trim((string) $this->platformSettings->get('ANTHROPIC_API_KEY', ''));
        $baseUrl = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com/v1'), '/');
        $musclesModel = (string) $this->platformSettings->get('AI_MUSCLES_MODEL', 'claude-haiku-4-5-20251001');
        $creditPauseReason = Cache::get('ai:anthropic:insufficient_credit');

        $report = [
            'api_key_configured' => $apiKey !== '',
            'base_url' => $baseUrl,
            'models_ok' => null,
            'models_count' => null,
            'inference_ok' => null,
            'inference_model' => $musclesModel,
            'inference_error' => null,
            'credits_status' => 'unknown',
            'credit_pause_active' => is_string($creditPauseReason) && trim($creditPauseReason) !== '',
            'credit_pause_reason' => is_string($creditPauseReason) ? $creditPauseReason : null,
            'error' => null,
        ];

        if ($apiKey === '') {
            $report['credits_status'] = 'not_configured';
            return $report;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                ])
                ->get($baseUrl . '/models');

            $report['models_ok'] = $response->successful();
            if ($response->successful()) {
                $payload = $response->json();
                $models = $payload['data'] ?? [];
                $report['models_count'] = is_array($models) ? count($models) : 0;
            } else {
                $report['error'] = 'HTTP ' . $response->status() . ': ' . $response->body();
            }

            $probe = Http::timeout(15)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ])
                ->post($baseUrl . '/messages', [
                    'model' => $musclesModel,
                    'max_tokens' => 8,
                    'system' => 'Health check',
                    'messages' => [
                        ['role' => 'user', 'content' => 'Reply with OK'],
                    ],
                ]);

            $report['inference_ok'] = $probe->successful();
            if ($probe->successful()) {
                $report['credits_status'] = 'available';
            } else {
                $probeJson = $probe->json() ?? [];
                $msg = (string) ($probeJson['error']['message']
                    ?? $probeJson['error']['detail']
                    ?? $probeJson['message']
                    ?? $probe->body());
                $report['inference_error'] = 'HTTP ' . $probe->status() . ': ' . $msg;
                $msgLower = strtolower($msg);
                $isInsufficientCredit = $probe->status() === 402
                    || str_contains($msgLower, 'payment required')
                    || ((str_contains($msgLower, 'credit') || str_contains($msgLower, 'balance') || str_contains($msgLower, 'billing'))
                        && (str_contains($msgLower, 'insufficient') || str_contains($msgLower, 'depleted') || str_contains($msgLower, 'too low') || str_contains($msgLower, 'exhausted')));
                $report['credits_status'] = $isInsufficientCredit ? 'insufficient' : 'unknown';
            }
        } catch (\Throwable $e) {
            $report['error'] = $e->getMessage();
        }

        return $report;
    }

    private function runTurnstileDiagnostics(): array
    {
        $enabled = (bool) config('services.turnstile.enabled', false);
        $siteKey = trim((string) config('services.turnstile.site_key', ''));
        $secretKey = trim((string) config('services.turnstile.secret_key', ''));

        $report = [
            'enabled' => $enabled,
            'site_key_configured' => $siteKey !== '',
            'secret_key_configured' => $secretKey !== '',
            'site_key_preview' => $siteKey !== '' ? substr($siteKey, 0, 8) . '...' : null,
            'verify_endpoint_ok' => null,
            'secret_valid' => null,
            'error_codes' => [],
            'hostname' => null,
            'error' => null,
        ];

        if (!$enabled || $secretKey === '') {
            return $report;
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => 'diagnostics-test-token',
                ]);

            $report['verify_endpoint_ok'] = $response->successful();
            $payload = $response->json() ?? [];
            $errorCodes = $payload['error-codes'] ?? [];

            if (!is_array($errorCodes)) {
                $errorCodes = [];
            }

            $report['error_codes'] = $errorCodes;
            $report['hostname'] = $payload['hostname'] ?? null;

            // With a fake token, a valid secret should usually return invalid-input-response.
            if (in_array('invalid-input-secret', $errorCodes, true)) {
                $report['secret_valid'] = false;
            } elseif (in_array('invalid-input-response', $errorCodes, true) || ($payload['success'] ?? false) === true) {
                $report['secret_valid'] = true;
            } else {
                $report['secret_valid'] = null;
            }
        } catch (\Throwable $e) {
            $report['error'] = $e->getMessage();
        }

        return $report;
    }
}
