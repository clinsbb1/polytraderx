<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($plan = $request->get('plan')) {
            $query->where('subscription_plan', $plan);
        }

        if ($request->get('active') !== null) {
            $query->where('is_active', $request->boolean('active'));
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $user->load('credential', 'payments');
        $tradeStats = [
            'total' => $user->trades()->count(),
            'won' => $user->trades()->won()->count(),
            'lost' => $user->trades()->lost()->count(),
            'open' => $user->trades()->open()->count(),
            'total_pnl' => $user->trades()->whereNotNull('pnl')->sum('pnl'),
        ];

        return view('admin.users.show', compact('user', 'tradeStats'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function changePlan(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'subscription_plan' => ['required', 'string', 'in:free_trial,basic,pro'],
        ]);

        $user->update([
            'subscription_plan' => $request->subscription_plan,
            'subscription_ends_at' => $request->subscription_plan !== 'free_trial'
                ? now()->addDays(30)
                : null,
        ]);

        return back()->with('success', "User plan changed to {$request->subscription_plan}.");
    }
}
