<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxInsightsSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_ipo_and_institutional_activity_are_synchronized(): void
    {
        config([
            'market_data.upstox.access_token' => 'test-token',
            'market_data.upstox.ipos_url' => 'https://provider.test/v2/ipos',
            'market_data.upstox.fii_url' => 'https://provider.test/v2/market/fii',
            'market_data.upstox.dii_url' => 'https://provider.test/v2/market/dii',
        ]);

        Http::fake(function (Request $request) {
            if ($request->url() === 'https://provider.test/v2/ipos/example-ipo') {
                return Http::response(['status' => 'success', 'data' => [
                    'id' => 'example-ipo',
                    'symbol' => 'EXAMPLE',
                    'name' => 'Example IPO',
                    'status' => 'open',
                    'isin' => 'INE000000001',
                    'issue_type' => 'regular',
                    'issue_size' => 500,
                    'minimum_price' => 100,
                    'maximum_price' => 110,
                    'bidding_start_date' => '2026-10-15',
                    'bidding_end_date' => '2026-10-19',
                    'lot_size' => 100,
                    'timeline' => ['listing_date' => '2026-10-26'],
                    'total_subscription' => '2.5',
                ]]);
            }

            if (str_starts_with($request->url(), 'https://provider.test/v2/ipos?')) {
                $data = $request['status'] === 'open' ? [[
                    'id' => 'example-ipo',
                    'symbol' => 'EXAMPLE',
                    'name' => 'Example IPO',
                    'status' => 'open',
                    'issue_type' => 'regular',
                ]] : [];

                return Http::response([
                    'status' => 'success',
                    'data' => $data,
                    'meta_data' => ['page' => ['total_pages' => 1]],
                ]);
            }

            $investorType = str_contains($request->url(), '/fii?') ? 'fii' : 'dii';
            $dataType = $investorType === 'fii' ? 'NSE_FO|INDEX_FUTURES' : 'NSE_EQ|CASH';

            return Http::response(['status' => 'success', 'data' => [
                $dataType => [[
                    'time_stamp' => 1792175400000,
                    'buy_amount' => 1200.5,
                    'sell_amount' => 1000.25,
                    'buy_contracts' => 12,
                    'sell_contracts' => 10,
                    'oi_contracts' => 25,
                    'oi_amount' => 3000,
                    'total_long_contracts' => 15,
                    'total_short_contracts' => 10,
                    'total_call_long_contracts' => 0,
                    'total_put_long_contracts' => 0,
                    'total_call_short_contracts' => 0,
                    'total_put_short_contracts' => 0,
                ]],
            ]]);
        });

        $this->artisan('market:sync-upstox-insights', ['--delay' => 0])
            ->expectsOutputToContain('IPOs synchronized. Received: 1; saved: 1')
            ->expectsOutputToContain('Institutional activity synchronized. Received: 4; saved: 4')
            ->assertExitCode(0);

        $this->assertDatabaseHas('ipos', [
            'slug' => 'example-ipo',
            'status' => 'open',
            'total_subscription' => 2.5,
        ]);
        $this->assertDatabaseHas('institutional_activities', [
            'investor_type' => 'fii',
            'data_type' => 'NSE_FO|INDEX_FUTURES',
            'interval' => '1D',
        ]);
        $this->assertDatabaseCount('institutional_activities', 4);
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer test-token'));
    }
}
