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

            // Telegram bot
            $platformSettings = app(PlatformSettingsService::class);
            $services['telegram_bot'] = $platformSettings->get('TELEGRAM_BOT_TOKEN') ? 'configured' : 'not_configured';

            // Anthropic
            $services['anthropic'] = $platformSettings->get('ANTHROPIC_API_KEY') ? 'configured' : 'not_configured';
            $services['polymarket_signer'] = $platformSettings->get('POLYMARKET_SIGNER_URL') ? 'configured' : 'not_configured';

            $overallStatus = collect($services)->contains('error') ? 'degraded' : 'ok';

            $stats = [
                'active_users' => User::where('is_active', true)->count(),
                'trades_today' => Trade::whereDate('created_at', today())->count(),
            ];

            $payload = [
                'status' => $overallStatus,
                'timestamp' => now()->toIso8601String(),
            ];

            if ($isSuperAdmin) {
                $payload['services'] = $services;
                $payload['stats'] = $stats;
            }

            return new JsonResponse($payload);
        });
    }
}
