<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Settings\PlatformSettingsService;
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
        PlatformSetting::firstOrCreate(
            ['key' => 'TELEGRAM_WEBHOOK_SECRET'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'telegram',
                'description' => 'Secret token used to verify Telegram webhook requests',
            ]
        );

        $settings = PlatformSetting::whereNotIn('key', self::HIDDEN_KEYS)
            ->orderBy('group')
            ->orderBy('key')
            ->get();
        $groups = $settings->groupBy('group');

        return view('admin.settings.index', compact('groups'));
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

    public function telegramDiagnostics(): View
    {
        $report = $this->runTelegramDiagnostics();

        return view('admin.settings.telegram-diagnostics', compact('report'));
    }

    public function serviceDiagnostics(): View
    {
        $telegram = $this->runTelegramDiagnostics();
        $polymarket = $this->runPolymarketPublicDiagnostics();
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

    private function runPolymarketPublicDiagnostics(): array
    {
        $report = [
            'base_url' => config('services.polymarket.base_url', 'https://clob.polymarket.com'),
            'time_ok' => null,
            'time_payload' => null,
            'markets_ok' => null,
            'markets_count' => null,
            'http_error' => null,
        ];

        try {
            $base = rtrim((string) $report['base_url'], '/');
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
        } catch (\Throwable $e) {
            $report['http_error'] = $e->getMessage();
        }

        return $report;
    }

    private function runAnthropicDiagnostics(): array
    {
        $apiKey = trim((string) $this->platformSettings->get('ANTHROPIC_API_KEY', ''));
        $baseUrl = rtrim((string) config('services.anthropic.base_url', 'https://api.anthropic.com/v1'), '/');

        $report = [
            'api_key_configured' => $apiKey !== '',
            'base_url' => $baseUrl,
            'models_ok' => null,
            'models_count' => null,
            'error' => null,
        ];

        if ($apiKey === '') {
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
