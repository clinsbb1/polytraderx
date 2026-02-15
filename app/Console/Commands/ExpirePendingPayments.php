<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Email\LifecycleEmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';
    protected $description = 'Mark pending payments as expired after 5 hours';

    public function handle(LifecycleEmailService $emails): int
    {
        $fiveHoursAgo = now()->subHours(5);

        // Find all pending payments older than 5 hours
        $expiredPayments = Payment::pending()
            ->where('created_at', '<', $fiveHoursAgo)
            ->get();

        if ($expiredPayments->isEmpty()) {
            $this->info('No pending payments to expire.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expiredPayments as $payment) {
            $payment->update([
                'status' => 'expired',
                'notes' => ($payment->notes ? $payment->notes . ' | ' : '') . 'Auto-expired after 5 hours',
            ]);

            $emails->sendPendingPaymentExpired($payment);

            Log::channel('simulator')->info('Payment expired', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'nowpayments_id' => $payment->nowpayments_id,
                'amount_usd' => $payment->amount_usd,
                'created_at' => $payment->created_at,
                'age_hours' => $payment->created_at->diffInHours(now()),
            ]);

            $count++;
        }

        $this->info("Expired {$count} pending payment(s).");

        return Command::SUCCESS;
    }
}
