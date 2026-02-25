<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Email\LifecycleEmailService;
use App\Services\Payment\NOWPaymentsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private NOWPaymentsService $paymentService,
        private SubscriptionService $subscriptionService,
        private LifecycleEmailService $emails,
    ) {}

    public function nowpayments(Request $request): JsonResponse
    {
        $signature = $request->header('x-nowpayments-sig', '');
        $body = $request->getContent();

        if (!$this->paymentService->verifyIPN($body, $signature)) {
            Log::channel('simulator')->warning('NOWPayments IPN: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        $paymentId = $data['payment_id'] ?? null;
        $status = $data['payment_status'] ?? null;

        if (!$paymentId || !$status) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $result = DB::transaction(function () use ($paymentId, $status, $data): array {
            $payment = Payment::where('nowpayments_id', (string) $paymentId)->lockForUpdate()->first();

            if (!$payment) {
                return ['error' => 'Payment not found', 'code' => 404];
            }

            $previousStatus = $payment->status;

            $payment->update([
                'status' => $status,
                'amount_crypto' => $data['actually_paid'] ?? null,
                'currency' => $data['pay_currency'] ?? null,
                'ipn_data' => $data,
            ]);

            if (!in_array($status, ['finished', 'confirmed'], true) || $payment->paid_at !== null) {
                return [
                    'ok' => true,
                    'activated' => false,
                    'payment_id' => $payment->id,
                    'status_changed' => $previousStatus !== $status,
                    'previous_status' => $previousStatus,
                ];
            }

            $user = $payment->user;
            $plan = $payment->subscriptionPlan;

            if (!$user || !$plan) {
                return [
                    'ok' => true,
                    'activated' => false,
                    'payment_id' => $payment->id,
                    'status_changed' => $previousStatus !== $status,
                    'previous_status' => $previousStatus,
                ];
            }

            $endsAt = $this->subscriptionService->activateSubscription($user->id, $plan, $payment);

            $payment->update([
                'paid_at' => now(),
                'expires_at' => $endsAt,
            ]);

            Log::channel('simulator')->info('Subscription activated via IPN', [
                'user_id' => $user->id,
                'plan' => $plan->slug,
                'payment_id' => $paymentId,
                'expires_at' => $endsAt->toIso8601String(),
            ]);

            return ['ok' => true, 'activated' => true];
        });

        if (isset($result['error'])) {
            Log::channel('simulator')->warning('NOWPayments IPN: payment not found', ['payment_id' => $paymentId]);
            return response()->json(['error' => $result['error']], $result['code']);
        }

        if (!empty($result['payment_id']) && !empty($result['status_changed'])) {
            $payment = Payment::with(['user', 'subscriptionPlan'])->find($result['payment_id']);
            if ($payment) {
                $this->emails->sendPaymentStatus($payment, $status, $result['previous_status'] ?? null);

                if (!empty($result['activated']) && $payment->user && $payment->subscriptionPlan) {
                    $this->emails->sendSubscriptionActivated(
                        $payment->user,
                        $payment->subscriptionPlan,
                        $payment->expires_at
                    );
                    $this->emails->sendAdminPaymentNotification($payment);
                }
            }
        }

        return response()->json(['ok' => true]);
    }
}
