<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\EquityApiController;
use App\Http\Controllers\Api\MarketDataController;
use App\Http\Controllers\Api\MfApiController;
use App\Models\Equity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class MarketMetadataVisibilityTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_synced_stock_metadata_is_visible_in_public_apis_and_admin_views(): void
    {
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries Limited',
            'short_name' => 'Reliance',
            'nse_symbol' => 'RELIANCE',
            'series' => 'EQ',
            'security_type' => 'NORMAL',
            'market_lot' => 1,
            'qty_multiplier' => 1,
            'mtf_enabled' => true,
            'mtf_bracket' => 26.5,
            'cas_eligible' => true,
            'intraday_margin' => 20,
            'intraday_leverage' => 5,
            'nse_tick_size' => 5,
            'nse_freeze_quantity' => 100000,
            'is_active' => true,
        ]);

        DB::table('equities')->where('id', $equity->id)->update([
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
            'nse_exchange_token' => '2885',
            'upstox_synced_at' => now(),
        ]);

        $equities = app(EquityApiController::class)
            ->index(Request::create('/api/v1/equities'));
        $equityPayload = json_decode($equities->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($equityPayload['data']['data'][0]['mtf_enabled']);
        $this->assertSame(5.0, (float) $equityPayload['data']['data'][0]['intraday_leverage']);
        $this->assertSame(100000.0, (float) $equityPayload['data']['data'][0]['nse_freeze_quantity']);
        $this->assertStringNotContainsString('instrument_key', $equities->getContent());
        $this->assertStringNotContainsString('exchange_token', $equities->getContent());

        $stocks = app(MarketDataController::class)
            ->stocks(Request::create('/api/v1/market/stocks', 'GET', ['instrument_type' => 'stocks']));
        $stockPayload = json_decode($stocks->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $stockPayload['data'][0]['market_lot']);
        $this->assertTrue($stockPayload['data'][0]['mtf_enabled']);
        $this->assertEquals(5.0, $stockPayload['data'][0]['tick_size']['nse']);
        $this->assertEquals(100000.0, $stockPayload['data'][0]['freeze_quantity']['nse']);
        $this->assertStringNotContainsString('instrument_key', $stocks->getContent());

        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->get(route('equities.show', $equity))
            ->assertOk()
            ->assertSee('Exchange Trading Metadata')
            ->assertSee('Quantity Multiplier')
            ->assertSee('100000');
    }

    public function test_mutual_fund_nav_and_fallback_policy_are_visible_in_api_and_admin(): void
    {
        $fundId = DB::table('mutual_funds')->insertGetId([
            'isin' => 'INF000000001',
            'scheme_code' => '123456',
            'scheme_name' => 'Example Growth Fund',
            'amc_name' => 'Example Mutual Fund',
            'category' => 'Equity',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mutual_fund_prices')->insert([
            'mf_id' => $fundId,
            'isin' => 'INF000000001',
            'nav_date' => '2026-10-07',
            'nav' => 15.4321,
            'created_at' => now(),
        ]);

        $response = app(MfApiController::class)->list(Request::create('/api/v1/mf/list'));
        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2026-10-07', $payload['data'][0]['nav_date']);
        $this->assertSame('AMFI', $payload['sources']['primary']);
        $this->assertSame('Upstox mutual-fund instruments', $payload['sources']['fallback']);

        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->get(route('mutual-funds.index'))
            ->assertOk()
            ->assertSee('07 Oct 2026')
            ->assertSee('Upstox fallback');
    }
}
