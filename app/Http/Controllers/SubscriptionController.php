<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Payment\NOWPaymentsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private NOWPaymentsService $paymentService,
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $currentPlan = $this->subscriptionService->getUserPlan($user);
        $plans = $this->subscriptionService->getAvailablePlans();
        $freeModeEnabled = $this->subscriptionService->isFreeModeEnabled();

        return view('subscription.index', compact('user', 'currentPlan', 'plans', 'freeModeEnabled'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $plan = SubscriptionPlan::active()->findOrFail($request->integer('plan_id'));
        $user = Auth::user();
        session()->put('analytics_subscription_previous_plan', (string) $user->subscription_plan);

        try {
            $invoice = $this->paymentService->createInvoice($user, $plan);

            if (!$invoice || !isset($invoice['invoice_url'])) {
                return back()->with('error', 'Payment processing failed. Please contact admin.');
            }

            return redirect($invoice['invoice_url']);
        } catch (\Exception $e) {
            // Log the technical error for admin debugging
            Log::channel('simulator')->error('Subscription checkout failed', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Payment processing failed. Please contact admin.');
        }
    }

    public function success(): View
    {
        $user = Auth::user();
        $previousPlan = (string) session()->pull('analytics_subscription_previous_plan', '');
        $currentPlan = (string) ($user?->subscription_plan ?? '');
        $eventName = $this->resolveSubscriptionEventName($previousPlan, $currentPlan);

        if ($eventName !== null) {
            session()->flash('analytics_events', [
                [
                    'name' => $eventName,
                    'params' => ['plan' => $currentPlan],
                ],
            ]);
        }

        return view('subscription.success');
    }

    public function cancel(): View
    {
        return view('subscription.cancel');
    }

    private function resolveSubscriptionEventName(string $previousPlan, string $currentPlan): ?string
    {
        if ($currentPlan === 'lifetime') {
            return 'lifetime_purchased';
        }

        if (!in_array($currentPlan, ['pro', 'advanced'], true)) {
            return null;
        }

        if ($previousPlan === '') {
            return 'subscription_started';
        }

        if (in_array($previousPlan, ['free', 'free_trial'], true)) {
            return 'subscription_started';
        }

        if (in_array($previousPlan, ['pro', 'advanced'], true) && $previousPlan !== $currentPlan) {
            return 'subscription_upgraded';
        }

        return null;
    }
}
