<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Settings\PlatformSettingsService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(private PlatformSettingsService $platformSettings) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            if (!$this->isValidWebhookRequest($request)) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $update = $request->all();

            Log::channel('bot')->info('Telegram webhook received', [
                'update_id' => $update['update_id'] ?? null,
                'has_message' => isset($update['message']),
            ]);

            if (empty($update)) {
                return response()->json(['error' => 'Empty update'], 400);
            }

            app(TelegramBotService::class)->handleWebhookUpdate($update);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::channel('bot')->error('Telegram webhook error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['ok' => true]);
        }
    }

    private function isValidWebhookRequest(Request $request): bool
    {
        $configuredSecret = trim((string) $this->platformSettings->get('TELEGRAM_WEBHOOK_SECRET', ''));
        $providedSecret = trim((string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''));

        if ($configuredSecret === '') {
            Log::channel('bot')->warning('Telegram webhook secret is not configured');
            return false;
        }

        return $providedSecret !== '' && hash_equals($configuredSecret, $providedSecret);
    }
}
