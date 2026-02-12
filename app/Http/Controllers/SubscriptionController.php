<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\Payment\NOWPaymentsService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private NOWPaymentsService $paymentService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $currentPlan = $this->subscriptionService->getActivePlan($user->id);
        $plans = $this->subscriptionService->getAvailablePlans();

        return view('subscription.index', compact('user', 'currentPlan', 'plans'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $user = auth()->user();

        $invoice = $this->paymentService->createInvoice($user, $plan);

        if (!$invoice || !isset($invoice['invoice_url'])) {
            return back()->with('error', 'Failed to create payment invoice. Please try again.');
        }

        return redirect($invoice['invoice_url']);
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
