<?php

declare(strict_types=1);

namespace App\Services\Telegram;

use App\Jobs\SendTelegramMessageJob;
use App\Models\AiAudit;
use App\Models\DailySummary;
use App\Models\Trade;
use App\Models\User;
use App\Services\Settings\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private NotificationFormatter $formatter,
        private SettingsService $settings,
    ) {}

    public function notifyTradeExecuted(Trade $trade): void
    {
        $user = User::find($trade->user_id);
        if (!$this->shouldNotify($user, 'NOTIFY_EACH_TRADE')) {
            return;
        }

        $this->send($user, $this->formatter->formatTradeExecuted($trade));
    }

    public function notifyTradeResolved(Trade $trade): void
    {
        $user = User::find($trade->user_id);
        if (!$this->shouldNotify($user, 'NOTIFY_EACH_TRADE')) {
            return;
        }

        $this->send($user, $this->formatter->formatTradeResolved($trade));
    }

    public function notifyDailySummary(DailySummary $summary, User $user): void
    {
        if (!$this->shouldNotify($user, 'NOTIFY_DAILY_PNL')) {
            return;
        }

        $this->send($user, $this->formatter->formatDailySummary($summary, $user));
    }

    public function notifyWeeklyReport(array $weekData, User $user): void
    {
        if (!$this->shouldNotify($user, 'NOTIFY_WEEKLY_REPORT')) {
            return;
        }

        $this->send($user, $this->formatter->formatWeeklyReport($weekData, $user));
    }

    public function notifyLossAudit(AiAudit $audit, Trade $trade): void
    {
        $user = User::find($trade->user_id);
        if (!$this->shouldNotify($user, 'NOTIFY_AI_AUDITS')) {
            return;
        }

        $this->send($user, $this->formatter->formatLossAudit($audit, $trade));
    }

    public function notifyBalanceAlert(float $balance, User $user): void
    {
        if (!$this->shouldNotify($user, 'NOTIFY_BALANCE_ALERTS')) {
            return;
        }

        // Throttle: max once per hour
        $cacheKey = "balance_alert:{$user->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        $threshold = $this->settings->getFloat('LOW_BALANCE_THRESHOLD', 20.0, $user->id);
        $this->send($user, $this->formatter->formatBalanceAlert($balance, $threshold, $user));
        Cache::put($cacheKey, true, 3600);
    }

    public function notifyDrawdownAlert(float $dailyPnl, float $drawdownPct, User $user): void
    {
        if (!$this->shouldNotify($user, 'NOTIFY_BALANCE_ALERTS')) {
            return;
        }

        // Throttle: max once per hour
        $cacheKey = "drawdown_alert:{$user->id}";
        if (Cache::has($cacheKey)) {
            return;
        }

        $threshold = $this->settings->getFloat('DRAWDOWN_ALERT_PERCENTAGE', 25.0, $user->id);
        $this->send($user, $this->formatter->formatDrawdownAlert($dailyPnl, $drawdownPct, $threshold, $user));
        Cache::put($cacheKey, true, 3600);
    }

    public function notifyError(string $error, ?string $context, User $user): void
    {
        if (!$this->shouldNotify($user, 'NOTIFY_ERRORS')) {
            return;
        }

        // Throttle: max 5 error notifications per hour per user
        $cacheKey = "error_notif_count:{$user->id}";
        $count = (int) Cache::get($cacheKey, 0);
        if ($count >= 5) {
            return;
        }

        $this->send($user, $this->formatter->formatErrorAlert($error, $context, $user));
        Cache::put($cacheKey, $count + 1, 3600);
    }

    public function notifySubscriptionActivated(User $user, string $planName, ?Carbon $expiresAt): void
    {
        if (!$user->hasTelegramLinked()) {
            return;
        }

        $this->send($user, $this->formatter->formatSubscriptionActivated($user, $planName, $expiresAt));
    }

    public function notifySubscriptionExpiring(User $user, int $daysLeft): void
    {
        if (!$user->hasTelegramLinked()) {
            return;
        }

        $this->send($user, $this->formatter->formatSubscriptionExpiring($user, $daysLeft));
    }

    public function notifySubscriptionExpired(User $user): void
    {
        if (!$user->hasTelegramLinked()) {
            return;
        }

        $this->send($user, $this->formatter->formatSubscriptionExpired($user));
    }

    public function notifyBotPaused(string $reason, User $user): void
    {
        if (!$user->hasTelegramLinked()) {
            return;
        }

        $this->send($user, $this->formatter->formatBotPaused($reason, $user));
    }

    public function notifyWelcome(User $user): void
    {
        if (!$user->hasTelegramLinked()) {
            return;
        }

        $this->send($user, $this->formatter->formatWelcome($user));
    }

    private function shouldNotify(?User $user, string $preferenceKey): bool
    {
        if (!$user || !$user->hasTelegramLinked()) {
            return false;
        }

        return $this->settings->getBool($preferenceKey, false, $user->id);
    }

    private function send(User $user, string $message): void
    {
        try {
            SendTelegramMessageJob::dispatch($user->id, $message)
                ->onQueue((string) config('services.queues.telegram', 'telegram'));
        } catch (\Exception $e) {
            Log::channel('simulator')->warning('Notification send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
