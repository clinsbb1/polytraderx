<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $isSuperAdmin = auth()->check() && auth()->user()->isSuperAdmin();
        $cacheKey = $isSuperAdmin ? 'health_check:superadmin' : 'health_check:public';

        return Cache::remember($cacheKey, 30, function () use ($isSuperAdmin): JsonResponse {
            if (!$isSuperAdmin) {
                return new JsonResponse([
                    'status' => 'ok',
                    'timestamp' => now()->toIso8601String(),
                ]);
            }

            $services = [];

            // Database
            try {
                DB::select('SELECT 1');
                $services['database'] = 'ok';
            } catch (\Throwable) {
                $services['database'] = 'error';
            }

            // Binance
            try {
                $response = Http::timeout(5)->get('https://api.binance.com/api/v3/ping');
                $services['binance'] = $response->successful() ? 'ok' : 'degraded';
            } catch (\Throwable) {
                $services['binance'] = 'error';
            }

            try {
                $response = Http::timeout(5)->get('https://clob.polymarket.com/time');
                $services['polymarket_public'] = $response->successful() ? 'ok' : 'degraded';
            } catch (\Throwable) {
                $services['polymarket_public'] = 'error';
            }

            // Telegram bot
            $platformSettings = app(PlatformSettingsService::class);
            $telegramToken = trim((string) $platformSettings->get('TELEGRAM_BOT_TOKEN', ''));
            if ($telegramToken === '') {
                $services['telegram_bot'] = 'not_configured';
            } else {
                try {
                    $telegram = Http::timeout(5)->get('https://api.telegram.org/bot' . $telegramToken . '/getMe');
                    $services['telegram_bot'] = $telegram->successful() ? 'ok' : 'degraded';
                } catch (\Throwable) {
                    $services['telegram_bot'] = 'error';
                }
            }

            // Anthropic
            $anthropicKey = trim((string) $platformSettings->get('ANTHROPIC_API_KEY', ''));
            if ($anthropicKey === '') {
                $services['anthropic'] = 'not_configured';
            } else {
                try {
                    $anthropic = Http::timeout(5)
                        ->withHeaders([
                            'x-api-key' => $anthropicKey,
                            'anthropic-version' => '2023-06-01',
                        ])
                        ->get('https://api.anthropic.com/v1/models');

                    $services['anthropic'] = $anthropic->successful() ? 'ok' : 'degraded';
                } catch (\Throwable) {
                    $services['anthropic'] = 'error';
                }
            }

            $overallStatus = collect($services)->contains('error') ? 'degraded' : 'ok';

            $stats = [
                'active_users' => User::where('is_active', true)->count(),
                'trades_today' => Trade::whereDate('created_at', today())->count(),
            ];

            $payload = [
                'status' => $overallStatus,
                'timestamp' => now()->toIso8601String(),
                'services' => $services,
                'stats' => $stats,
            ];

            return new JsonResponse($payload);
        });
    }
}
