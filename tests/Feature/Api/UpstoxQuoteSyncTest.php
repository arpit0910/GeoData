<?php

namespace Tests\Feature\Api;

use App\Events\StockPriceUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxQuoteSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'cache.default' => 'array',
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.quote_url' => 'https://api.upstox.test/v3/market-quote/quotes',
        ]);
        Event::fake([StockPriceUpdated::class]);
    }

    public function test_command_fetches_every_stock_in_batches_of_at_most_500(): void
    {
        $now = now();
        $rows = [];
        foreach (range(1, 501) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
            $rows[] = [
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => 'STOCK'.$index,
                'series' => 'EQ',
                'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('equities')->insert($chunk);
        }

        Http::fake(fn (Request $request) => Http::response($this->quoteResponse($request)));

        $this->artisan('market:sync-upstox-quotes')
            ->expectsOutputToContain('Batches: 2; requested: 501; saved: 501')
            ->assertExitCode(0);
        $this->assertSame(501, DB::table('equity_quotes')->count());

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $count = count(explode(',', $query['instrument_key'] ?? ''));
            return $count >= 1 && $count <= 500
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
        Event::assertDispatched(StockPriceUpdated::class, 501);

        $payload = (string) DB::table('equity_quotes')->value('payload');
        $this->assertStringNotContainsString('instrument_token', $payload);
        $this->assertStringNotContainsString('NSE_EQ|', $payload);
        $this->assertStringContainsString('"provider":"upstox"', $payload);
    }

    public function test_command_fails_cleanly_without_an_access_token(): void
    {
        DB::table('equities')->insert([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'series' => 'EQ',
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        config(['market_data.upstox.access_token' => null]);

        $this->artisan('market:sync-upstox-quotes')
            ->expectsOutputToContain('No active Upstox token is available.')
            ->assertExitCode(1);
        Http::assertNothingSent();
        $this->assertSame(0, DB::table('equity_quotes')->count());
    }

    public function test_command_stores_nse_and_bse_quotes_in_the_same_isin_record(): void
    {
        DB::table('equities')->insert([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'bse_symbol' => '500325',
            'series' => 'EQ',
            'upstox_nse_instrument_key' => 'NSE_EQ|INE002A01018',
            'upstox_bse_instrument_key' => 'BSE_EQ|INE002A01018',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Http::fake(fn (Request $request) => Http::response($this->quoteResponse($request)));

        $this->artisan('market:sync-upstox-quotes', ['--isin' => ['INE002A01018']])
            ->expectsOutputToContain('Batches: 1; requested: 2; saved: 2')
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('equity_quotes')->count());
        $row = DB::table('equity_quotes')->first();
        $this->assertNotNull($row->nse_price);
        $this->assertNotNull($row->bse_price);
        $this->assertSame('NSE', $row->exchange);
    }

    public function test_command_stops_after_the_first_unauthorized_batch(): void
    {
        $now = now();
        $rows = [];
        foreach (range(1, 501) as $index) {
            $isin = sprintf('INE%06d%03d', $index, $index % 1000);
            $rows[] = [
                'isin' => $isin,
                'company_name' => 'Company '.$index,
                'nse_symbol' => 'STOCK'.$index,
                'series' => 'EQ',
                'upstox_nse_instrument_key' => 'NSE_EQ|'.$isin,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('equities')->insert($chunk);
        }

        config([
            'market_data.upstox.client_id' => null,
            'market_data.upstox.client_secret' => null,
        ]);
        Http::fake([
            '*' => Http::response(['status' => 'error', 'message' => 'Unauthorized'], 401),
        ]);

        $this->artisan('market:sync-upstox-quotes')
            ->expectsOutputToContain('Upstox batch 1 failed')
            ->expectsOutputToContain('Remaining Upstox batches were skipped')
            ->doesntExpectOutputToContain('Upstox batch 2 failed')
            ->assertExitCode(1);

        Http::assertSentCount(1);
        $this->assertSame(0, DB::table('equity_quotes')->count());
    }

    /** @return array<string, mixed> */
    private function quoteResponse(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $keys = explode(',', $query['instrument_key'] ?? '');
        $data = [];

        foreach ($keys as $position => $key) {
            $symbol = 'STOCK'.($position + 1);
            $data['NSE_EQ:'.$symbol] = [
                'instrument_token' => $key,
                'symbol' => $symbol,
                'last_price' => 125.50,
                'prev_close_price' => 124.00,
                'net_change' => 1.50,
                'volume' => 10000,
                'average_price' => 125.10,
                'last_trade_time' => '1789962300000',
                'timestamp' => '2026-09-21T10:35:00+05:30',
                'ohlc' => [
                    'open' => 124.50,
                    'high' => 126.00,
                    'low' => 123.75,
                    'close' => 124.00,
                    'volume' => 10000,
                    'ts' => 1789929900000,
                ],
            ];
        }

        return ['status' => 'success', 'data' => $data];
    }
}
