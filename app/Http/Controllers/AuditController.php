<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\AI\AIRouter;
use App\Services\Subscription\SubscriptionService;
use App\Services\Telegram\NotificationService;
use App\Services\Trading\StrategyUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AuditController extends Controller
{
    private const MANUAL_TRIGGER_COOLDOWN_SECONDS = 600;

    public function index(Request $request): View
    {
        $query = AiAudit::forUser(auth()->id());

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($trigger = $request->get('trigger')) {
            $query->where('trigger', $trigger);
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $audits = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('audits.index', compact('audits'));
    }

    public function show(AiAudit $audit): View
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        $losingTrades = $audit->losingTrades();

        return view('audits.show', compact('audit', 'losingTrades'));
    }

    public function approveFix(Request $request, AiAudit $audit): RedirectResponse
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        $fixIndex = (int) $request->input('fix_index', 0);
        $fixes = $audit->suggested_fixes ?? [];

        if (!isset($fixes[$fixIndex])) {
            return redirect()->route('audits.show', $audit)
                ->with('error', 'Invalid fix index.');
        }

        $fixes[$fixIndex]['status'] = 'approved';

        $audit->update([
            'suggested_fixes' => $fixes,
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', $audit->review_notes ?? ''),
        ]);

        // Apply the fix if it has a param and suggested value
        $fix = $fixes[$fixIndex];
        if (!empty($fix['param']) && isset($fix['suggested'])) {
            /** @var StrategyUpdater $updater */
            $updater = app(StrategyUpdater::class);
            $updater->applyFix((int) $audit->user_id, $fix['param'], (string) $fix['suggested']);
        }

        // If all fixes are resolved (approved or rejected), update audit status
        $allResolved = collect($fixes)->every(fn (array $f): bool => in_array($f['status'] ?? 'pending_review', ['approved', 'rejected', 'auto_applied'], true));

        if ($allResolved) {
            $hasApproved = collect($fixes)->contains(fn (array $f): bool => ($f['status'] ?? '') === 'approved');
            $audit->update([
                'status' => $hasApproved ? 'approved' : 'rejected',
                'applied_at' => $hasApproved ? now() : null,
            ]);
        }

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix approved and applied.');
    }

    public function manualTrigger(): RedirectResponse
    {
        $userId = (int) auth()->id();
        $cooldownKey = "audits:manual-trigger:{$userId}";
        $user = auth()->user();

        if (Cache::has($cooldownKey)) {
            return redirect()->route('audits.index')
                ->with('error', 'Audit already triggered recently. Please wait 10 minutes before trying again.');
        }

        if ($user) {
            $subscriptionService = app(SubscriptionService::class);
            $dailyCap = $subscriptionService->getMaxAiBrainPerDay($user);
            $brainCallsToday = $subscriptionService->getAiBrainCallsToday($user);

            if ($dailyCap > 0 && $brainCallsToday >= $dailyCap) {
                return redirect()->route('audits.index')
                    ->with('error', 'AI analysis quota used for this cycle. Deep audit limit reached today. Try again tomorrow.');
            }
        }

        Cache::put($cooldownKey, true, now()->addSeconds(self::MANUAL_TRIGGER_COOLDOWN_SECONDS));

        $losses = Trade::forUser($userId)
            ->where('status', 'lost')
            ->where('audited', false)
            ->orderBy('resolved_at', 'asc')
            ->limit(5)
            ->get();

        $audited = 0;
        $router = app(AIRouter::class);
        $notifications = app(NotificationService::class);

        foreach ($losses as $trade) {
            $result = $router->requestLossAudit($trade, $userId);

            if (is_array($result) && ($result['status'] ?? null) === 'ai_limit_reached') {
                return redirect()->route('audits.index')
                    ->with('warning', $result['message']);
            }

            if ($result instanceof AiAudit) {
                $audited++;
                $notifications->notifyLossAudit($result, $trade);
            }
        }
        session()->flash('analytics_events', [
            ['name' => 'ai_audit_triggered'],
        ]);

        return redirect()->route('audits.index')
            ->with('success', $audited > 0
                ? "AI analysis completed for {$audited} trade(s). Core simulation continues normally."
                : 'AI analysis quota used for this cycle. Core simulation continues normally.');
    }

    public function rejectFix(Request $request, AiAudit $audit): RedirectResponse
    {
        abort_if((int) $audit->user_id !== auth()->id(), 403);

        $fixIndex = (int) $request->input('fix_index', 0);
        $fixes = $audit->suggested_fixes ?? [];

        if (!isset($fixes[$fixIndex])) {
            return redirect()->route('audits.show', $audit)
                ->with('error', 'Invalid fix index.');
        }

        $fixes[$fixIndex]['status'] = 'rejected';
        $fixes[$fixIndex]['reject_reason'] = $request->input('reason', '');

        $audit->update([
            'suggested_fixes' => $fixes,
            'reviewed_at' => now(),
            'review_notes' => $request->input('notes', $audit->review_notes ?? ''),
        ]);

        // If all fixes are resolved, update audit status
        $allResolved = collect($fixes)->every(fn (array $f): bool => in_array($f['status'] ?? 'pending_review', ['approved', 'rejected', 'auto_applied'], true));

        if ($allResolved) {
            $hasApproved = collect($fixes)->contains(fn (array $f): bool => ($f['status'] ?? '') === 'approved');
            $audit->update([
                'status' => $hasApproved ? 'approved' : 'rejected',
            ]);
        }

        return redirect()->route('audits.show', $audit)
            ->with('success', 'Fix rejected.');
    }
}
