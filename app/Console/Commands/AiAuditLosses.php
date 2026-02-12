<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\AI\AIRouter;
use App\Services\Telegram\TelegramBotService;
use App\Services\UserBotRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AiAuditLosses extends Command
{
    protected $signature = 'bot:ai-audit-losses';
    protected $description = 'Run AI Brain audit on unaudited losing trades';

    public function handle(
        UserBotRunner $runner,
        AIRouter $aiRouter,
        TelegramBotService $telegram,
    ): int {
        $results = $runner->runForEachUser(function ($user) use ($aiRouter, $telegram) {
            $losses = Trade::forUser($user->id)
                ->where('status', 'lost')
                ->where('audited', false)
                ->orderBy('closed_at', 'asc')
                ->limit(5)
                ->get();

            $audited = 0;
            foreach ($losses as $trade) {
                $audit = $aiRouter->requestLossAudit($trade, $user->id);

                if ($audit !== null) {
                    $audited++;

                    $fixCount = count($audit->suggested_fixes ?? []);
                    $message = "<b>Loss Audit Complete</b>\n\n"
                        . "Trade: {$trade->asset} {$trade->side}\n"
                        . "Amount: \${$trade->amount}\n"
                        . "Root Cause: " . ($audit->analysis ? substr($audit->analysis, 0, 200) : 'N/A') . "\n"
                        . "Suggested Fixes: {$fixCount}\n"
                        . "Status: {$audit->status}";

                    $telegram->sendToUser($user->id, $message);
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
