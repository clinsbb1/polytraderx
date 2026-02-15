<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ContactSupportMail;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function landing(): View
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('public.landing', compact('plans'));
    }

    public function pricing(): View
    {
        $plans = SubscriptionPlan::active()->ordered()->get();

        return view('public.pricing', compact('plans'));
    }

    public function terms(): View
    {
        return view('public.terms');
    }

    public function privacy(): View
    {
        return view('public.privacy');
    }

    public function refundPolicy(): View
    {
        return view('public.refund-policy');
    }

    public function contact(): View
    {
        $user = auth()->user();
        $canSubmitContact = $this->canUsePremiumSupportForm($user);

        return view('public.contact', compact('canSubmitContact'));
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (!$this->canUsePremiumSupportForm($user)) {
            return back()->with('error', 'Only Advanced and Early Bird Lifetime subscribers can use this contact form.');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'in:support,billing,bug,feature,other'],
            'message' => ['required', 'string', 'max:5000'],
            'screenshot' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        try {
            Mail::to('admin@polytraderx.xyz')->send(new ContactSupportMail(
                user: $user,
                topic: $validated['subject'],
                issueMessage: $validated['message'],
                screenshot: $request->file('screenshot')
            ));
        } catch (\Throwable $e) {
            Log::channel('simulator')->error('Contact form email send failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Failed to send your message right now. Please try again or email support@polytraderx.xyz.');
        }

        return back()->with('success', 'Your message has been sent to support.');
    }

    private function canUsePremiumSupportForm(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->subscription_plan === 'lifetime') {
            return true;
        }

        if ($user->subscription_plan !== 'advanced') {
            return false;
        }

        return $user->subscription_ends_at !== null && $user->subscription_ends_at->isFuture();
    }
}
