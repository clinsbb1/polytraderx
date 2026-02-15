<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::with('user', 'subscriptionPlan');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total_revenue' => Payment::where('status', 'finished')->sum('amount_usd'),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'this_month' => Payment::where('status', 'finished')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount_usd'),
        ];

        return view('admin.payments.index', compact('payments', 'stats'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirming,finished,confirmed,failed,expired,refunded'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $payment->status;

        $payment->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ? ($payment->notes ? $payment->notes . ' | ' : '') . $validated['notes'] : $payment->notes,
        ]);

        Log::channel('simulator')->info('Payment status updated by admin', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'old_status' => $oldStatus,
            'new_status' => $validated['status'],
            'admin_id' => auth()->id(),
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', "Payment status updated from {$oldStatus} to {$validated['status']}.");
    }
}
