<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AI\AIRouter;
use App\Services\Telegram\TelegramBotService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;

class SimDailyReview extends Command
{
    protected $signature = 'sim:daily-review';
    protected $description = 'Run AI Brain daily performance review for all users';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
        TelegramBotService $telegram,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($aiRouter, $telegram) {
            $audit = $aiRouter->requestDailyReview($user->id);

            if (is_array($audit)) {
                return ['status' => 'skipped', 'reason' => $audit['message'] ?? 'AI analysis quota used for this cycle.'];
            }

            if (!$audit instanceof \App\Models\AiAudit) {
                return ['status' => 'skipped', 'reason' => 'Brain unavailable or budget exceeded'];
            }

            $fixCount = count($audit->suggested_fixes ?? []);
            $message = "<b>Daily Review</b>\n\n"
                . substr($audit->analysis ?? 'No analysis', 0, 500) . "\n\n"
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
            $this->info('No active users available for simulation run.');
        }

        return Command::SUCCESS;
    }
}
