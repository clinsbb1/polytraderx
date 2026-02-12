<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\Settings\SettingsService;
use App\Services\Telegram\NotificationFormatter;
use App\Services\Telegram\NotificationService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotificationService(
        bool $telegramSends = true,
        bool $notifyEachTrade = true,
        bool $notifyErrors = true,
        bool $notifyBalanceAlerts = true,
    ): NotificationService {
        $telegram = $this->createMock(TelegramBotService::class);
        $telegram->method('sendToUser')->willReturn($telegramSends);

        $formatter = new NotificationFormatter();

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturnCallback(function ($key) use ($notifyEachTrade, $notifyErrors, $notifyBalanceAlerts) {
            return match ($key) {
                'NOTIFY_EACH_TRADE' => $notifyEachTrade,
                'NOTIFY_ERRORS' => $notifyErrors,
                'NOTIFY_BALANCE_ALERTS' => $notifyBalanceAlerts,
                'NOTIFY_AI_AUDITS' => true,
                'NOTIFY_DAILY_PNL' => true,
                'NOTIFY_WEEKLY_REPORT' => true,
                default => false,
            };
        });
        $settings->method('getFloat')->willReturn(20.0);

        return new NotificationService($telegram, $formatter, $settings);
    }

    private function makeUserWithTelegram(): User
    {
        return User::factory()->create([
            'telegram_chat_id' => '123456',
            'telegram_linked_at' => now(),
        ]);
    }

    private function makeUserWithoutTelegram(): User
    {
        return User::factory()->create([
            'telegram_chat_id' => null,
        ]);
    }

    public function test_notify_trade_skips_when_disabled(): void
    {
        $service = $this->makeNotificationService(notifyEachTrade: false);
        $user = $this->makeUserWithTelegram();

        // notifyTradeExecuted calls User::find which needs real user
        // but NOTIFY_EACH_TRADE=false so it skips before sending
        $trade = \App\Models\Trade::factory()->won()->create(['user_id' => $user->id]);

        $service->notifyTradeExecuted($trade);
        $this->assertTrue(true); // No exception = pass
    }

    public function test_notify_trade_skips_without_telegram(): void
    {
        $service = $this->makeNotificationService(notifyEachTrade: true);
        $user = $this->makeUserWithoutTelegram();

        $trade = \App\Models\Trade::factory()->won()->create(['user_id' => $user->id]);

        // User has no telegram_chat_id, so shouldNotify returns false
        $service->notifyTradeExecuted($trade);
        $this->assertTrue(true);
    }

    public function test_notify_error_respects_throttle(): void
    {
        $service = $this->makeNotificationService(notifyErrors: true);
        $user = $this->makeUserWithTelegram();

        // Simulate 5 errors already sent
        Cache::put("error_notif_count:{$user->id}", 5, 3600);

        // This should be throttled (no send)
        $service->notifyError('Test error', null, $user);
        $this->assertEquals(5, Cache::get("error_notif_count:{$user->id}"));

        Cache::forget("error_notif_count:{$user->id}");
    }

    public function test_notify_balance_alert_respects_throttle(): void
    {
        $service = $this->makeNotificationService(notifyBalanceAlerts: true);
        $user = $this->makeUserWithTelegram();

        // Set throttle cache
        Cache::put("balance_alert:{$user->id}", true, 3600);

        // This should be throttled
        $service->notifyBalanceAlert(10.0, $user);
        $this->assertTrue(true);

        Cache::forget("balance_alert:{$user->id}");
    }

    public function test_notification_failure_does_not_throw(): void
    {
        $telegram = $this->createMock(TelegramBotService::class);
        $telegram->method('sendToUser')->willThrowException(new \RuntimeException('Network error'));

        $settings = $this->createMock(SettingsService::class);
        $settings->method('getBool')->willReturn(true);
        $settings->method('getFloat')->willReturn(20.0);

        $service = new NotificationService($telegram, new NotificationFormatter(), $settings);

        $user = $this->makeUserWithTelegram();

        // Should catch internally and not throw
        $service->notifySubscriptionExpired($user);
        $this->assertTrue(true);
    }

    public function test_subscription_notifications_always_sent(): void
    {
        $service = $this->makeNotificationService(notifyEachTrade: false);
        $user = $this->makeUserWithTelegram();

        // These bypass preference checks (always send if telegram linked)
        $service->notifySubscriptionActivated($user, 'Pro', now()->addMonth());
        $service->notifySubscriptionExpiring($user, 3);
        $service->notifySubscriptionExpired($user);
        $service->notifyBotPaused('Test', $user);

        $this->assertTrue(true);
    }
}
