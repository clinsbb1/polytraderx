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

        return view('subscription.index', compact('user', 'currentPlan', 'plans'));
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
        return view('subscription.success');
    }

    public function cancel(): View
    {
        return view('subscription.cancel');
    }
}
