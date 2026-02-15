<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Mail\BrandedNotificationMail;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LifecycleEmailService
{
    public function sendWelcome(User $user): bool
    {
        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Welcome to PolyTraderX',
                headline: "You're in. Let's start simulating.",
                lines: [
                    "Hi {$user->name}, your account is ready.",
                    'PolyTraderX helps you test market strategies in simulation mode with real data, no real-money risk.',
                    'Open your dashboard to configure strategy settings, notification preferences, and subscription options.',
                ],
                actionText: 'Open Dashboard',
                actionUrl: url('/dashboard'),
            ),
            'welcome_email'
        );
    }

    public function sendPasswordReset(User $user, string $resetUrl): bool
    {
        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Reset your PolyTraderX password',
                headline: 'Password reset requested',
                lines: [
                    "Hi {$user->name}, we received a request to reset your password.",
                    'If this was you, use the button below to choose a new password.',
                    'If you did not request this, you can safely ignore this email.',
                ],
                actionText: 'Reset Password',
                actionUrl: $resetUrl,
                smallPrint: 'For security, this link expires automatically.'
            ),
            'password_reset_email'
        );
    }

    public function sendPaymentStatus(Payment $payment, string $newStatus, ?string $previousStatus = null): bool
    {
        $payment->loadMissing('user', 'subscriptionPlan');
        $user = $payment->user;
        $planName = $payment->subscriptionPlan?->name ?? 'Subscription';

        if (!$user) {
            return false;
        }

        $headline = match ($newStatus) {
            'finished', 'confirmed' => 'Payment received',
            'failed', 'expired' => 'Payment did not complete',
            default => 'Payment status updated',
        };

        $lines = [
            "Your {$planName} payment status is now: {$newStatus}.",
        ];

        if ($previousStatus !== null && $previousStatus !== $newStatus) {
            $lines[] = "Previous status: {$previousStatus}.";
        }

        if (in_array($newStatus, ['finished', 'confirmed'], true)) {
            $lines[] = 'Your subscription has been activated.';
        }

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: "Payment update: {$newStatus}",
                headline: $headline,
                lines: $lines,
                actionText: 'View Subscription',
                actionUrl: url('/subscription'),
                meta: [
                    'Plan' => $planName,
                    'Amount (USD)' => '$' . number_format((float) $payment->amount_usd, 2),
                    'NOWPayments ID' => (string) ($payment->nowpayments_id ?: '-'),
                    'Status' => $newStatus,
                ],
            ),
            'payment_status_email',
            [
                'payment_id' => $payment->id,
                'status' => $newStatus,
            ]
        );
    }

    public function sendSubscriptionActivated(User $user, SubscriptionPlan $plan, ?Carbon $expiresAt): bool
    {
        $meta = [
            'Plan' => $plan->name,
            'Billing' => ucfirst($plan->billing_period),
            'Expires' => $expiresAt ? $expiresAt->format('M j, Y g:i A T') : 'Never (lifetime)',
        ];

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: "Subscription activated: {$plan->name}",
                headline: 'Your subscription is active',
                lines: [
                    "Your {$plan->name} plan is now active.",
                    'You can start using the plan limits and features immediately.',
                ],
                actionText: 'Go to Dashboard',
                actionUrl: url('/dashboard'),
                meta: $meta,
            ),
            'subscription_activated_email'
        );
    }

    public function sendFreeSubscriptionGranted(User $user, SubscriptionPlan $plan, ?int $durationDays = null): bool
    {
        $isLifetime = $plan->slug === 'lifetime';
        $durationLabel = $isLifetime
            ? 'Lifetime'
            : (($durationDays ?? 0) > 0 ? "{$durationDays} days" : 'Custom duration');

        $lines = [
            "An admin has granted you free access to the {$plan->name} plan.",
        ];

        if ($isLifetime) {
            $lines[] = 'This gift grants lifetime access for the life of the product.';
        } else {
            $lines[] = "Duration: {$durationLabel}.";
        }

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: "Free {$plan->name} subscription granted",
                headline: 'Free subscription applied',
                lines: $lines,
                actionText: 'View Subscription',
                actionUrl: url('/subscription'),
                meta: [
                    'Plan' => $plan->name,
                    'Duration' => $durationLabel,
                ],
            ),
            'free_subscription_granted_email'
        );
    }

    public function sendSubscriptionExpiring(User $user, int $daysLeft, ?Carbon $expiresAt): bool
    {
        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: "Subscription reminder: {$daysLeft} day(s) left",
                headline: 'Your subscription is ending soon',
                lines: [
                    "Your current access will expire in {$daysLeft} day(s).",
                    'Renew early to avoid interruptions.',
                ],
                actionText: 'Renew Subscription',
                actionUrl: url('/subscription'),
                meta: [
                    'Current Plan' => ucfirst((string) $user->subscription_plan),
                    'Expires At' => $expiresAt ? $expiresAt->format('M j, Y g:i A T') : 'N/A',
                ],
            ),
            'subscription_expiring_email'
        );
    }

    public function sendPendingPaymentExpired(Payment $payment): bool
    {
        $payment->loadMissing('user', 'subscriptionPlan');
        $user = $payment->user;

        if (!$user) {
            return false;
        }

        $planName = $payment->subscriptionPlan?->name ?? 'Subscription';

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Payment expired after 5 hours',
                headline: 'Pending payment auto-expired',
                lines: [
                    "Your pending payment for {$planName} was not completed within 5 hours and is now marked as expired.",
                    'You can retry anytime from your subscription page.',
                ],
                actionText: 'Retry Payment',
                actionUrl: url('/subscription'),
                meta: [
                    'Plan' => $planName,
                    'Amount (USD)' => '$' . number_format((float) $payment->amount_usd, 2),
                    'Payment ID' => (string) ($payment->nowpayments_id ?: '#'.$payment->id),
                ],
            ),
            'pending_payment_expired_email',
            ['payment_id' => $payment->id]
        );
    }

    public function sendSubscriptionExpired(User $user, ?string $previousPlan = null): bool
    {
        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Your plan has expired',
                headline: 'Subscription expired',
                lines: [
                    'Your paid subscription has ended and your account access has been downgraded.',
                    'You can reactivate immediately by choosing a plan.',
                ],
                actionText: 'Choose a Plan',
                actionUrl: url('/subscription'),
                meta: [
                    'Previous Plan' => $previousPlan ? ucfirst($previousPlan) : ucfirst((string) $user->subscription_plan),
                ],
            ),
            'subscription_expired_email'
        );
    }

    private function sendToUser(User $user, BrandedNotificationMail $mail, string $event, array $context = []): bool
    {
        if (empty($user->email)) {
            return false;
        }

        try {
            Mail::to($user->email)->send($mail);
            return true;
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Lifecycle email failed', array_merge($context, [
                'event' => $event,
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]));

            return false;
        }
    }
}
