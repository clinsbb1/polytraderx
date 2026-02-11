<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
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

    public function contact(): View
    {
        return view('public.contact');
    }
}
