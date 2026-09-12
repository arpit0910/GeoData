<?php

namespace Tests\Feature\Api;

use App\Models\Equity;
use App\Services\EquityQuoteService;
use App\Services\MarketDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Tests\TestCase;

class MarketDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        (require database_path('migrations/2026_04_12_182044_create_equities_table.php'))->up();
        (require database_path('migrations/2026_09_09_000000_create_equity_quotes_table.php'))->up();
        Event::fake();
        $this->travelTo(now()->setDate(2026, 9, 9)->setTime(10, 0));
    }

    private function response(float $price = 125, ?int $time = null): array
    {
        return ['chart' => ['error' => null, 'result' => [['meta' => [
            'regularMarketPrice' => $price, 'chartPreviousClose' => 100,
            'regularMarketTime' => $time ?? now()->timestamp,
            'currency' => 'INR', 'exchangeName' => 'NSI',
        ]]]]];
    }

    private function batchResponse(array $symbols, float $price = 125, ?int $time = null): array
    {
        return ['spark' => ['error' => null, 'result' => collect($symbols)->map(fn ($symbol) => [
            'symbol' => $symbol,
            'response' => [[
                'meta' => [
                    'regularMarketPrice' => $price,
                    'chartPreviousClose' => 100,
                    'regularMarketTime' => $time ?? now()->timestamp,
                    'currency' => 'INR',
                    'exchangeName' => 'NSI',
                ],
            ]],
        ])->all()]];
    }

    public function test_command_saves_both_exchanges_and_api_reads_without_network(): void
    {
        Equity::create(['isin' => 'INE002A01018', 'nse_symbol' => 'RELIANCE', 'bse_symbol' => '500325', 'is_active' => true]);
        Http::fake(['*' => Http::response($this->batchResponse(['RELIANCE.NS', '500325.BO']))]);
        $this->artisan('market:fetch-live')->assertExitCode(0);
        $this->assertSame(2, DB::table('equity_quotes')->count());
        $this->assertDatabaseHas('equity_quotes', ['isin' => 'INE002A01018', 'exchange' => 'NSE', 'symbol' => 'RELIANCE.NS']);
        $this->assertDatabaseHas('equity_quotes', ['exchange' => 'BSE', 'symbol' => '500325.BO']);
        Http::assertSentCount(1);
        $this->getJson('/api/v1/market/equity/INE002A01018')->assertOk()
            ->assertJsonPath('data.0.isin', 'INE002A01018')->assertJsonPath('data.0.is_stale', false);
        Http::assertSentCount(1);
    }

    public function test_older_quotes_and_provider_failures_preserve_saved_value(): void
    {
        Equity::create(['isin' => 'INE002A01018', 'nse_symbol' => 'RELIANCE', 'is_active' => true]);
        Http::fakeSequence()
            ->push($this->batchResponse(['RELIANCE.NS']))
            ->push($this->batchResponse(['RELIANCE.NS'], 90, now()->subHour()->timestamp))
            ->push([], 503);
        $this->artisan('market:fetch-live')->assertExitCode(0);
        $this->artisan('market:fetch-live')->assertExitCode(0);
        $this->artisan('market:fetch-live')->assertExitCode(1);
        $this->assertSame(1, DB::table('equity_quotes')->count());
        $this->assertDatabaseHas('equity_quotes', ['price' => 125]);
        $this->travel(16)->minutes();
        $this->getJson('/api/v1/market/equity/INE002A01018')->assertOk()->assertJsonPath('data.0.is_stale', true);
    }

    public function test_invalid_price_is_unavailable_and_unknown_isin_is_not_found(): void
    {
        $response = $this->response();
        $response['chart']['result'][0]['meta']['regularMarketPrice'] = 'invalid';
        Http::fake(['*' => Http::response($response)]);
        $this->assertSame('unavailable', app(MarketDataService::class)->getQuote('RELIANCE.NS')['source']);
        $this->assertSame(0, DB::table('equity_quotes')->count());
        $this->getJson('/api/v1/market/equity/INE002A01018')->assertNotFound();
        $this->getJson('/api/v1/market/equity/bad')->assertStatus(422);
    }

    public function test_isin_filter_only_fetches_selected_active_equity(): void
    {
        Equity::create(['isin' => 'INE002A01018', 'nse_symbol' => 'RELIANCE', 'is_active' => true]);
        Equity::create(['isin' => 'INE009A01021', 'nse_symbol' => 'INFY', 'is_active' => true]);
        Http::fake(['*' => Http::response($this->batchResponse(['RELIANCE.NS']))]);
        $this->artisan('market:fetch-live', ['--isin' => ['INE002A01018']])->assertExitCode(0);
        Http::assertSentCount(1);
        $this->assertSame(1, DB::table('equity_quotes')->count());
        $this->artisan('market:fetch-live', ['symbols' => ['UNKNOWN.NS']])->assertExitCode(1);
    }

    public function test_bulk_fetch_respects_yahoo_twenty_symbol_batch_limit(): void
    {
        foreach (range(1, 21) as $index) {
            Equity::create([
                'isin' => sprintf('INE%06d01018', $index),
                'nse_symbol' => 'STOCK'.$index,
                'is_active' => true,
            ]);
        }

        Http::fake(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $symbols = explode(',', $query['symbols'] ?? '');
            return Http::response($this->batchResponse($symbols));
        });

        $this->artisan('market:fetch-live')->assertExitCode(0);
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            return count(explode(',', $query['symbols'] ?? '')) <= 20;
        });
        $this->assertSame(21, DB::table('equity_quotes')->count());
    }
}
