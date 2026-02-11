<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\Payment\NOWPaymentsService;
use App\Services\Subscription\SubscriptionService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private NOWPaymentsService $paymentService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function nowpayments(Request $request): JsonResponse
    {
        $signature = $request->header('x-nowpayments-sig', '');
        $body = $request->getContent();

        if (!$this->paymentService->verifyIPN($body, $signature)) {
            Log::channel('bot')->warning('NOWPayments IPN: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        $paymentId = $data['payment_id'] ?? null;
        $status = $data['payment_status'] ?? null;

        if (!$paymentId || !$status) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $payment = Payment::where('nowpayments_id', (string) $paymentId)->first();

        if (!$payment) {
            Log::channel('bot')->warning('NOWPayments IPN: payment not found', ['payment_id' => $paymentId]);

            return response()->json(['error' => 'Payment not found'], 404);
        }

        $payment->update([
            'status' => $status,
            'amount_crypto' => $data['actually_paid'] ?? null,
            'currency' => $data['pay_currency'] ?? null,
            'ipn_data' => $data,
        ]);

        if (in_array($status, ['finished', 'confirmed'])) {
            $user = $payment->user;
            $plan = $payment->subscriptionPlan;

            if ($user && $plan) {
                $this->subscriptionService->activateSubscription($user->id, $plan, $payment);

                Log::channel('bot')->info('Subscription activated via IPN', [
                    'user_id' => $user->id,
                    'plan' => $plan->slug,
                    'payment_id' => $paymentId,
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function telegram(Request $request): JsonResponse
    {
        try {
            $update = $request->all();

            if (empty($update)) {
                return response()->json(['error' => 'Empty update'], 400);
            }

            app(TelegramBotService::class)->handleWebhookUpdate($update);

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::channel('bot')->error('Telegram webhook error', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => true]);
        }
    }
}
