<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendTelegramMessageJob;
use App\Services\AI\AIRouter;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class SimWeeklyReport extends Command
{
    protected $signature = 'sim:weekly-report';
    protected $description = 'Run AI Brain weekly performance deep analysis for all users';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($aiRouter) {
            $audit = $aiRouter->requestWeeklyReport($user->id);

            if (is_array($audit)) {
                return ['status' => 'skipped', 'reason' => $audit['message'] ?? 'AI analysis quota used for this cycle.'];
            }

            if (!$audit instanceof \App\Models\AiAudit) {
                return ['status' => 'skipped', 'reason' => 'Brain unavailable or budget exceeded'];
            }

            $fixCount = count($audit->suggested_fixes ?? []);
            $message = "<b>Weekly Report</b>\n\n"
                . substr($audit->analysis ?? 'No analysis', 0, 800) . "\n\n"
                . "Suggested Changes: {$fixCount}\n"
                . "Review in your dashboard to approve/reject.";

            SendTelegramMessageJob::dispatch($user->id, $message)
                ->onQueue((string) config('services.queues.telegram', 'telegram'));

            return ['status' => 'completed', 'audit_id' => $audit->id, 'fixes' => $fixCount];
        });

        foreach ($results as $userId => $result) {
            if ($result['status'] === 'error') {
                $this->error("User #{$userId}: {$result['error']}");
            } else {
                $r = $result['result'];
                $this->info("User #{$userId}: {$r['status']}" . (isset($r['audit_id']) ? " (audit #{$r['audit_id']})" : ''));
            }
        }

        if (empty($results)) {
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
