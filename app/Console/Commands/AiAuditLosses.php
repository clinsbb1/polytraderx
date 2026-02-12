<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\AI\AIRouter;
use App\Services\Telegram\NotificationService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class AiAuditLosses extends Command
{
    protected $signature = 'bot:ai-audit-losses';
    protected $description = 'Run AI Brain audit on unaudited losing trades';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
        NotificationService $notifications,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($aiRouter, $notifications) {
            $losses = Trade::forUser($user->id)
                ->where('status', 'lost')
                ->where('audited', false)
                ->orderBy('resolved_at', 'asc')
                ->limit(5)
                ->get();

            $audited = 0;
            foreach ($losses as $trade) {
                $audit = $aiRouter->requestLossAudit($trade, $user->id);

                if ($audit !== null) {
                    $audited++;
                    $notifications->notifyLossAudit($audit, $trade);
                }
            }

            return ['losses_found' => $losses->count(), 'audited' => $audited];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info("User #{$userId}: {$r['losses_found']} losses, {$r['audited']} audited");
            }
        }

        if (empty($results)) {
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
