<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
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
}
