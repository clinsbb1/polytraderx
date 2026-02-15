<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Settings\PlatformSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NOWPaymentsService
{
    private string $apiKey;
    private string $ipnSecret;
    private string $baseUrl;

    public function __construct(private PlatformSettingsService $platformSettings)
    {
        // Trim whitespace from API credentials
        $this->apiKey = trim((string) $this->platformSettings->get('NOWPAYMENTS_API_KEY', ''));
        $this->ipnSecret = trim((string) $this->platformSettings->get('NOWPAYMENTS_IPN_SECRET', ''));

        $sandbox = $this->platformSettings->getBool('NOWPAYMENTS_SANDBOX_MODE', true);
        $this->baseUrl = $sandbox
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';

        // Log configuration for debugging (without exposing full key)
        Log::channel('simulator')->info('NOWPayments configured', [
            'sandbox_mode' => $sandbox,
            'base_url' => $this->baseUrl,
            'api_key_length' => strlen($this->apiKey),
            'api_key_first_4' => substr($this->apiKey, 0, 4),
        ]);
    }

    public function createInvoice(User $user, SubscriptionPlan $plan): ?array
    {
        // Check if API key is configured
        if (empty($this->apiKey)) {
            Log::channel('simulator')->error('NOWPayments API key not configured');
            throw new \Exception('Payment system is not configured. Please contact admin.');
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/invoice", [
                'price_amount' => (float) $plan->price_usd,
                'price_currency' => 'usd',
                'order_id' => "user_{$user->id}_plan_{$plan->id}_" . time(),
                'order_description' => "PolyTraderX {$plan->name} Subscription",
                'ipn_callback_url' => url('/api/webhooks/nowpayments'),
                'success_url' => url('/subscription/success'),
                'cancel_url' => url('/subscription/cancel'),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'billing_interval' => match ($plan->billing_period) {
                        'lifetime' => 'lifetime',
                        'yearly' => 'yearly',
                        default => 'monthly',
                    },
                    'nowpayments_id' => (string) ($data['id'] ?? ''),
                    'amount_usd' => $plan->price_usd,
                    'status' => 'pending',
                    'payment_url' => $data['invoice_url'] ?? null,
                ]);

                return $data;
            }

            // Get error message from API response
            $errorData = $response->json();
            $errorMessage = $errorData['message'] ?? $errorData['error'] ?? 'Unknown error';

            // Log all technical details for admin debugging
            Log::channel('simulator')->error('NOWPayments invoice creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $errorMessage,
                'sandbox_mode' => $this->platformSettings->getBool('NOWPAYMENTS_SANDBOX_MODE', true),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            // Throw generic user-friendly error
            throw new \Exception('Payment processing failed. Please contact admin.');
        } catch (\Exception $e) {
            Log::channel('simulator')->error('NOWPayments invoice exception', [
                'message' => $e->getMessage(),
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ]);

            throw $e;
        }
    }

    public function getPaymentStatus(string $paymentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/payment/{$paymentId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::channel('simulator')->error('NOWPayments status check failed', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function verifyIPN(string $requestBody, string $signature): bool
    {
        if (empty($this->ipnSecret) || empty($signature)) {
            return false;
        }

        $hmac = hash_hmac('sha512', $requestBody, $this->ipnSecret);

        return hash_equals($hmac, $signature);
    }

    public function getAvailableCurrencies(): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
            ])->get("{$this->baseUrl}/currencies");

            if ($response->successful()) {
                return $response->json('currencies', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::channel('simulator')->error('NOWPayments currencies fetch failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
