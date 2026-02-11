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
        return view('audits.index');
    }

    public function show(AiAudit $audit): View
    {
        return view('audits.show', compact('audit'));
    }

    public function approveFix(Request $request, AiAudit $audit): RedirectResponse
    {
        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix approved.');
    }

    public function rejectFix(Request $request, AiAudit $audit): RedirectResponse
    {
        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix rejected.');
    }
}
