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
            "An admin has granted you complimentary access to the {$plan->name} plan.",
        ];

        if ($isLifetime) {
            $lines[] = 'This gift grants lifetime access for the life of the product.';
        } else {
            $lines[] = "Duration: {$durationLabel}.";
        }

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: "Complimentary {$plan->name} subscription granted",
                headline: 'Complimentary subscription applied',
                lines: $lines,
                actionText: 'View Subscription',
                actionUrl: url('/subscription'),
                meta: [
                    'Plan' => $plan->name,
                    'Duration' => $durationLabel,
                ],
            ),
            'complimentary_subscription_granted_email'
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

    public function sendFreeAccessRevoked(User $user): bool
    {
        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Your free access has been deactivated',
                headline: 'Free plan deactivated',
                lines: [
                    "Hi {$user->name}, the free tier on PolyTraderX has been deactivated.",
                    'Your account has been paused. All your data, trades, and strategy settings are safe.',
                    'Choose a paid plan to resume simulating immediately.',
                ],
                actionText: 'View Plans',
                actionUrl: url('/subscription'),
            ),
            'free_access_revoked_email'
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

    public function sendCustomBotRequestNotification(\App\Models\CustomBotRequest $request): bool
    {
        return $this->sendToAddress(
            'support@polytraderx.xyz',
            new BrandedNotificationMail(
                subjectLine: "New custom bot request from {$request->name}",
                headline: 'New Custom Bot Build Request',
                lines: [
                    "{$request->name} has submitted a custom live bot build request.",
                    "Strategy summary: {$request->strategy_summary}",
                ],
                actionText: 'View Request',
                actionUrl: url('/admin/users/' . $request->user_id),
                meta: [
                    'Name' => $request->name,
                    'Email' => $request->email,
                    'Contact' => $request->contact ?: '-',
                    'Markets' => $request->markets ?: '-',
                    'Timeframe' => $request->timeframe ?: '-',
                    'Wants AI' => $request->wants_ai ? 'Yes' : 'No',
                    'Budget Range' => $request->budget_range ?: '-',
                    'Timeline' => $request->timeline ?: '-',
                ],
            ),
            'custom_bot_request',
            ['user_id' => $request->user_id, 'request_id' => $request->id]
        );
    }

    public function sendCustomBotRequestAccepted(\App\Models\CustomBotRequest $request, ?string $adminNotes = null): bool
    {
        $request->loadMissing('user');
        $user = $request->user;

        if (!$user) {
            return false;
        }

        $lines = [
            "Great news, {$user->name}! We've reviewed your custom live bot request and we'd like to move forward.",
            "Our team will reach out shortly to discuss next steps, timeline, and project kick-off.",
        ];

        if ($adminNotes) {
            $lines[] = "Message from our team: {$adminNotes}";
        }

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Your custom bot request has been accepted',
                headline: 'Custom bot request accepted',
                lines: $lines,
                actionText: 'View Dashboard',
                actionUrl: url('/dashboard'),
                meta: [
                    'Request Name' => $request->name,
                    'Budget Range' => $request->budget_range ?: '-',
                    'Timeline'     => $request->timeline ?: '-',
                    'Status'       => 'Accepted',
                ],
            ),
            'custom_bot_request_accepted',
            ['user_id' => $user->id, 'request_id' => $request->id]
        );
    }

    public function sendCustomBotRequestDeclined(\App\Models\CustomBotRequest $request, ?string $adminNotes = null): bool
    {
        $request->loadMissing('user');
        $user = $request->user;

        if (!$user) {
            return false;
        }

        $lines = [
            "Hi {$user->name}, thank you for submitting your custom bot build request.",
            "After reviewing your request, we're unable to take it on at this time.",
        ];

        if ($adminNotes) {
            $lines[] = "Reason: {$adminNotes}";
        } else {
            $lines[] = "Feel free to reach out at support@polytraderx.xyz if you have questions or want to discuss alternatives.";
        }

        return $this->sendToUser(
            $user,
            new BrandedNotificationMail(
                subjectLine: 'Update on your custom bot request',
                headline: 'Custom bot request update',
                lines: $lines,
                actionText: 'Contact Support',
                actionUrl: 'mailto:support@polytraderx.xyz',
                meta: [
                    'Request Name' => $request->name,
                    'Status'       => 'Not accepted at this time',
                ],
            ),
            'custom_bot_request_declined',
            ['user_id' => $user->id, 'request_id' => $request->id]
        );
    }

    public function sendAdminPaymentNotification(Payment $payment): bool
    {
        $payment->loadMissing('user', 'subscriptionPlan');
        $user = $payment->user;
        $plan = $payment->subscriptionPlan;

        if (!$user || !$plan) {
            return false;
        }

        return $this->sendToAddress(
            'support@polytraderx.xyz',
            new BrandedNotificationMail(
                subjectLine: "New payment: {$user->name} — {$plan->name}",
                headline: 'New subscription payment received',
                lines: [
                    "{$user->name} has successfully paid for the {$plan->name} plan.",
                ],
                actionText: 'View User',
                actionUrl: url('/admin/users/' . $user->id),
                meta: [
                    'Customer' => $user->name,
                    'Email' => $user->email,
                    'Plan' => $plan->name,
                    'Amount (USD)' => '$' . number_format((float) $payment->amount_usd, 2),
                    'NOWPayments ID' => (string) ($payment->nowpayments_id ?: '-'),
                    'Expires' => $payment->expires_at ? $payment->expires_at->format('M j, Y g:i A T') : 'N/A',
                ],
            ),
            'admin_payment_notification',
            ['payment_id' => $payment->id, 'user_id' => $user->id]
        );
    }

    private function sendToAddress(string $address, BrandedNotificationMail $mail, string $event, array $context = []): bool
    {
        try {
            Mail::to($address)->queue(
                $mail->onQueue((string) config('services.queues.email', 'emails'))
            );
            return true;
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Lifecycle email failed', array_merge($context, [
                'event' => $event,
                'to' => $address,
                'error' => $e->getMessage(),
            ]));

            return false;
        }
    }

    private function sendToUser(User $user, BrandedNotificationMail $mail, string $event, array $context = []): bool
    {
        if (empty($user->email)) {
            return false;
        }

        try {
            Mail::to($user->email)->queue(
                $mail->onQueue((string) config('services.queues.email', 'emails'))
            );
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
