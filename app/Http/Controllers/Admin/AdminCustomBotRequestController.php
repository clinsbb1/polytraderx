<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomBotRequest;
use App\Services\Email\LifecycleEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminCustomBotRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = CustomBotRequest::with('user');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'pending'   => CustomBotRequest::where('status', 'pending')->count(),
            'reviewing' => CustomBotRequest::where('status', 'reviewing')->count(),
            'accepted'  => CustomBotRequest::where('status', 'accepted')->count(),
            'declined'  => CustomBotRequest::where('status', 'declined')->count(),
        ];

        return view('admin.custom-bot-requests.index', compact('requests', 'stats'));
    }

    public function show(CustomBotRequest $customBotRequest): View
    {
        $customBotRequest->loadMissing('user');

        return view('admin.custom-bot-requests.show', ['botRequest' => $customBotRequest]);
    }

    public function updateStatus(Request $request, CustomBotRequest $customBotRequest, LifecycleEmailService $emailService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewing,accepted,declined'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $customBotRequest->status;
        $newStatus = $validated['status'];

        $customBotRequest->update([
            'status' => $newStatus,
        ]);

        Log::channel('simulator')->info('Custom bot request status updated by admin', [
            'request_id' => $customBotRequest->id,
            'user_id'    => $customBotRequest->user_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'admin_id'   => auth()->id(),
        ]);

        // Send email to user when status changes to accepted or declined
        if (in_array($newStatus, ['accepted', 'declined'], true) && $oldStatus !== $newStatus) {
            $customBotRequest->loadMissing('user');
            $adminNotes = $validated['admin_notes'] ?? null;

            if ($newStatus === 'accepted') {
                $emailService->sendCustomBotRequestAccepted($customBotRequest, $adminNotes);
            } else {
                $emailService->sendCustomBotRequestDeclined($customBotRequest, $adminNotes);
            }
        }

        return back()->with('success', "Status updated from {$oldStatus} to {$newStatus}.");
    }
}
