<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
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
}
