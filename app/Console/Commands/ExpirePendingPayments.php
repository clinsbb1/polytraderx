<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Email\LifecycleEmailService;
use App\Services\Payment\NOWPaymentsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';
    protected $description = 'Mark pending payments as expired after 5 hours, checking NOWPayments first';

    public function handle(
        LifecycleEmailService $emails,
        NOWPaymentsService $paymentService,
        SubscriptionService $subscriptionService,
    ): int {
        $fiveHoursAgo = now()->subHours(5);

        $pendingPayments = Payment::pending()
            ->where('created_at', '<', $fiveHoursAgo)
            ->get();

        if ($pendingPayments->isEmpty()) {
            $this->info('No pending payments to expire.');
            return Command::SUCCESS;
        }

        $expired = 0;
        $recovered = 0;

        foreach ($pendingPayments as $payment) {
            // If no NOWPayments ID, nothing to verify externally
            if (empty($payment->nowpayments_id)) {
                $this->expirePayment($payment, $emails, 'Auto-expired after 5 hours (no NOWPayments ID)');
                $expired++;
                continue;
            }

            // Check the real status on NOWPayments before expiring
            $apiData = $paymentService->getPaymentStatus($payment->nowpayments_id);
            $apiStatus = $apiData['payment_status'] ?? null;

            if (in_array($apiStatus, ['finished', 'confirmed'], true)) {
                // IPN was missed but payment actually succeeded — recover it
                $user = $payment->user;
                $plan = $payment->subscriptionPlan;

                if ($user && $plan) {
                    $endsAt = $subscriptionService->activateSubscription($user->id, $plan, $payment);

                    $payment->update([
                        'status'     => $apiStatus,
                        'paid_at'    => now(),
                        'expires_at' => $endsAt,
                        'ipn_data'   => $apiData,
                        'notes'      => ($payment->notes ? $payment->notes . ' | ' : '') . 'Recovered by expire-pending check (missed IPN)',
                    ]);

                    $emails->sendPaymentStatus($payment->fresh(), $apiStatus, 'pending');
                    $emails->sendSubscriptionActivated($user, $plan, $endsAt);
                    $emails->sendAdminPaymentNotification($payment->fresh());

                    Log::channel('simulator')->warning('ExpirePending: recovered missed IPN payment', [
                        'payment_id'      => $payment->id,
                        'nowpayments_id'  => $payment->nowpayments_id,
                        'user_id'         => $user->id,
                        'api_status'      => $apiStatus,
                        'expires_at'      => $endsAt?->toIso8601String(),
                    ]);

                    $recovered++;
                    continue;
                }
            }

            // API returned a non-success status or was unreachable — expire it
            $note = $apiData === null
                ? 'Auto-expired after 5 hours (NOWPayments API unreachable)'
                : "Auto-expired after 5 hours (NOWPayments status: {$apiStatus})";

            $this->expirePayment($payment, $emails, $note);
            $expired++;
        }

        $this->info("Expired: {$expired}, Recovered (missed IPN): {$recovered} payment(s).");

        return Command::SUCCESS;
    }

    private function expirePayment(Payment $payment, LifecycleEmailService $emails, string $note): void
    {
        $payment->update([
            'status' => 'expired',
            'notes'  => ($payment->notes ? $payment->notes . ' | ' : '') . $note,
        ]);

        $emails->sendPendingPaymentExpired($payment);

        Log::channel('simulator')->info('Payment expired', [
            'payment_id'     => $payment->id,
            'user_id'        => $payment->user_id,
            'nowpayments_id' => $payment->nowpayments_id,
            'amount_usd'     => $payment->amount_usd,
            'created_at'     => $payment->created_at,
            'age_hours'      => $payment->created_at->diffInHours(now()),
            'note'           => $note,
        ]);
    }
}
