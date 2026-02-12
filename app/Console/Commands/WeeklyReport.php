<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\AIRouter;
use App\Services\Telegram\TelegramBotService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class WeeklyReport extends Command
{
    protected $signature = 'bot:weekly-report';
    protected $description = 'Run AI Brain weekly performance deep analysis for all users';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
        TelegramBotService $telegram,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($aiRouter, $telegram) {
            $audit = $aiRouter->requestWeeklyReport($user->id);

            if ($audit === null) {
                return ['status' => 'skipped', 'reason' => 'Brain unavailable or budget exceeded'];
            }

            $fixCount = count($audit->suggested_fixes ?? []);
            $message = "<b>Weekly Report</b>\n\n"
                . substr($audit->analysis ?? 'No analysis', 0, 800) . "\n\n"
                . "Suggested Changes: {$fixCount}\n"
                . "Review in your dashboard to approve/reject.";

            $telegram->sendToUser($user->id, $message);

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
            $this->info('No active users with Polymarket credentials.');
        }

        return Command::SUCCESS;
    }
}
