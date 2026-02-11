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
        $this->apiKey = config('services.nowpayments.api_key', '');
        $this->ipnSecret = config('services.nowpayments.ipn_secret', '');

        $sandbox = config('services.nowpayments.sandbox', true);
        $this->baseUrl = $sandbox
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';
    }

    public function createInvoice(User $user, SubscriptionPlan $plan): ?array
    {
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
                    'nowpayments_id' => (string) ($data['id'] ?? ''),
                    'amount_usd' => $plan->price_usd,
                    'status' => 'pending',
                ]);

                return $data;
            }

            Log::channel('bot')->error('NOWPayments invoice creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::channel('bot')->error('NOWPayments invoice exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
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
            Log::channel('bot')->error('NOWPayments status check failed', [
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
            Log::channel('bot')->error('NOWPayments currencies fetch failed', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
