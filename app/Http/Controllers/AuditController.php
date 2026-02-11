<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        $audits = AiAudit::forUser(auth()->id())->latest()->paginate(20);

        return view('audits.index', compact('audits'));
    }

    public function show(AiAudit $audit): View
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        return view('audits.show', compact('audit'));
    }

    public function approveFix(Request $request, AiAudit $audit): RedirectResponse
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        $audit->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', ''),
        ]);

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix approved.');
    }

    public function rejectFix(Request $request, AiAudit $audit): RedirectResponse
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        $audit->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', ''),
        ]);

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix rejected.');
    }
}
