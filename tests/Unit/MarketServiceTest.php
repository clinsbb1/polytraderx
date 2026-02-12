<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Polymarket\MarketService;
use Carbon\Carbon;
use Tests\TestCase;

class MarketServiceTest extends TestCase
{
    private MarketService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketService();
    }

    public function test_identify_asset_btc(): void
    {
        $this->assertEquals('BTC', $this->service->identifyAsset('Will BTC be higher at 2:45 PM UTC?'));
    }

    public function test_identify_asset_eth(): void
    {
        $this->assertEquals('ETH', $this->service->identifyAsset('Will ETH be above $3500 at 3:00 PM?'));
    }

    public function test_identify_asset_sol(): void
    {
        $this->assertEquals('SOL', $this->service->identifyAsset('Will SOL increase by end of cycle?'));
    }

    public function test_identify_asset_bitcoin_full_name(): void
    {
        $this->assertEquals('BTC', $this->service->identifyAsset('Will Bitcoin be higher at 2:45 PM?'));
    }

    public function test_identify_asset_ethereum_full_name(): void
    {
        $this->assertEquals('ETH', $this->service->identifyAsset('Will Ethereum go up?'));
    }

    public function test_identify_asset_solana_full_name(): void
    {
        $this->assertEquals('SOL', $this->service->identifyAsset('Solana price prediction'));
    }

    public function test_identify_asset_unknown_returns_null(): void
    {
        $this->assertNull($this->service->identifyAsset('Will DOGE go to the moon?'));
    }

    public function test_identify_asset_case_insensitive(): void
    {
        $this->assertEquals('BTC', $this->service->identifyAsset('will btc be higher?'));
    }

    public function test_parse_market_prices_with_tokens(): void
    {
        $market = [
            'tokens' => [
                ['outcome' => 'Yes', 'price' => '0.96', 'token_id' => '0xyes123'],
                ['outcome' => 'No', 'price' => '0.04', 'token_id' => '0xno456'],
            ],
        ];

        $result = $this->service->parseMarketPrices($market);

        $this->assertEquals(0.96, $result['yes_price']);
        $this->assertEquals(0.04, $result['no_price']);
        $this->assertEquals('0xyes123', $result['yes_token_id']);
        $this->assertEquals('0xno456', $result['no_token_id']);
    }

    public function test_parse_market_prices_with_clob_ids(): void
    {
        $market = [
            'outcomePrices' => [0.85, 0.15],
            'clobTokenIds' => ['0xclob_yes', '0xclob_no'],
        ];

        $result = $this->service->parseMarketPrices($market);

        $this->assertEquals(0.85, $result['yes_price']);
        $this->assertEquals(0.15, $result['no_price']);
        $this->assertEquals('0xclob_yes', $result['yes_token_id']);
        $this->assertEquals('0xclob_no', $result['no_token_id']);
    }

    public function test_get_market_end_time_iso(): void
    {
        $market = ['end_date_iso' => '2026-02-12T15:00:00Z'];
        $result = $this->service->getMarketEndTime($market);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('2026-02-12 15:00:00', $result->utc()->format('Y-m-d H:i:s'));
    }

    public function test_get_market_end_time_timestamp(): void
    {
        $ts = Carbon::parse('2026-02-12T15:00:00Z')->timestamp;
        $market = ['end_time' => $ts];
        $result = $this->service->getMarketEndTime($market);

        $this->assertInstanceOf(Carbon::class, $result);
    }

    public function test_get_market_end_time_missing_returns_null(): void
    {
        $market = ['question' => 'Will BTC go up?'];
        $this->assertNull($this->service->getMarketEndTime($market));
    }
}
