<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Settings\SettingsService;
use App\Services\Trading\MarketTimingService;
use Carbon\Carbon;
use Tests\TestCase;

class MarketTimingServiceTest extends TestCase
{
    public function test_in_entry_window_30s_remaining_60s_window(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getInt')->willReturn(60);
        $service = new MarketTimingService($settings);

        $market = ['end_time' => now()->addSeconds(30)];
        $this->assertTrue($service->isInEntryWindow($market, 1));
    }

    public function test_outside_entry_window_90s_remaining_60s_window(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getInt')->willReturn(60);
        $service = new MarketTimingService($settings);

        $market = ['end_time' => now()->addSeconds(90)];
        $this->assertFalse($service->isInEntryWindow($market, 1));
    }

    public function test_expired_market_not_in_window(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getInt')->willReturn(60);
        $service = new MarketTimingService($settings);

        $market = ['end_time' => now()->subSeconds(10)];
        $this->assertFalse($service->isInEntryWindow($market, 1));
    }

    public function test_too_close_to_expiry_not_in_window(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getInt')->willReturn(60);
        $service = new MarketTimingService($settings);

        // Only 3 seconds left — below 5s buffer
        $market = ['end_time' => now()->addSeconds(3)];
        $this->assertFalse($service->isInEntryWindow($market, 1));
    }

    public function test_custom_entry_window_per_user(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $settings->method('getInt')->willReturn(120); // 2-minute window
        $service = new MarketTimingService($settings);

        $market = ['end_time' => now()->addSeconds(90)];
        $this->assertTrue($service->isInEntryWindow($market, 1));
    }

    public function test_get_seconds_remaining(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $service = new MarketTimingService($settings);

        $market = ['end_time' => now()->addSeconds(45)];
        $remaining = $service->getSecondsRemaining($market);

        $this->assertGreaterThanOrEqual(44, $remaining);
        $this->assertLessThanOrEqual(46, $remaining);
    }

    public function test_is_market_expired(): void
    {
        $settings = $this->createMock(SettingsService::class);
        $service = new MarketTimingService($settings);

        $this->assertTrue($service->isMarketExpired(['end_time' => now()->subMinutes(1)]));
        $this->assertFalse($service->isMarketExpired(['end_time' => now()->addMinutes(1)]));
    }
}
