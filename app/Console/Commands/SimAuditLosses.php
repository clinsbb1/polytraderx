<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AiAudit;
use App\Models\Trade;
use App\Services\AI\AIRouter;
use App\Services\Settings\PlatformSettingsService;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimAuditLosses extends Command
{
    protected $signature = 'sim:audit-losses';
    protected $description = 'Run AI Brain audit on unaudited losing trades';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
        PlatformSettingsService $platformSettings,
        NotificationService $notifications,
    ): int {
        $rechargedAtRaw = trim((string) $platformSettings->get('AI_AUDIT_RECHARGED_AT', ''));
        $rechargedAt = null;
        $rechargeMarkerStatus = 'ok';

        if ($rechargedAtRaw === '') {
            $rechargeMarkerStatus = 'missing';
        } else {
            try {
                $rechargedAt = Carbon::parse($rechargedAtRaw);
            } catch (\Throwable) {
                $rechargeMarkerStatus = 'invalid';
            }
        }

        $results = $runner->runForEachUser(function ($user) use ($aiRouter, $notifications, $rechargedAt, $rechargeMarkerStatus) {
            if (!$rechargedAt instanceof Carbon) {
                return [
                    'status' => 'skipped',
                    'reason' => $rechargeMarkerStatus === 'missing'
                        ? 'AI recharge marker not set by admin.'
                        : 'AI recharge marker is invalid. Update AI_AUDIT_RECHARGED_AT in admin settings.',
                    'losses_found' => 0,
                    'audited' => 0,
                ];
            }

            $losses = Trade::forUser($user->id)
                ->where('status', 'lost')
                ->where('audited', false)
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $rechargedAt)
                ->orderBy('resolved_at', 'asc')
                ->limit(5)
                ->get();

            $audited = 0;
            foreach ($losses as $trade) {
                $audit = $aiRouter->requestLossAudit($trade, $user->id);

                if ($audit instanceof AiAudit) {
                    $audited++;
                    $notifications->notifyLossAudit($audit, $trade);
                    continue;
                }

                if (is_array($audit)) {
                    break;
                }
            }

            return [
                'status' => 'processed',
                'losses_found' => $losses->count(),
                'audited' => $audited,
                'recharged_at' => $rechargedAt->toDateTimeString(),
            ];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                if (($r['status'] ?? null) === 'skipped') {
                    $this->warn("User #{$userId}: skipped ({$r['reason']})");
                } else {
                    $this->info(
                        "User #{$userId}: {$r['losses_found']} losses since {$r['recharged_at']}, {$r['audited']} audited"
                    );
                }
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
