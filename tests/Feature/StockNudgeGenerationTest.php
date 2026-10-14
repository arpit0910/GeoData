<?php

namespace Tests\Feature;

use App\Models\Equity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockNudgeGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_market_nudges_are_generated_and_deduplicated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-09 10:00:00', 'Asia/Kolkata'));
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Example Limited',
            'nse_symbol' => 'EXAMPLE',
        ]);
        $this->price($equity->id, '2026-09-09', 90, 100, 80, 95, 1000);

        $payload = json_encode(['dp' => 10, 'previous_close' => 100]);
        DB::table('equity_quotes')->insert([
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'symbol' => 'EXAMPLE',
            'price' => 110,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'payload' => $payload,
            'nse_symbol' => 'EXAMPLE',
            'nse_price' => 110,
            'nse_quoted_at' => now()->utc(),
            'nse_fetched_at' => now()->utc(),
            'nse_payload' => $payload,
        ]);

        $storedQuote = DB::table('equity_quotes')->first();
        $this->assertNotNull($storedQuote->nse_price);
        $this->assertFalse(
            Carbon::parse($storedQuote->nse_quoted_at, 'UTC')->lt(now('Asia/Kolkata')->subMinutes(15)),
            "The fixture's quote must be fresh."
        );

        $this->artisan('stock-nudges:generate market')->assertSuccessful();
        $this->assertDatabaseHas('stock_nudges', ['rule' => 'daily_gain', 'type' => 'market']);
        $this->assertDatabaseHas('stock_nudges', ['rule' => 'year_high', 'type' => 'market']);
        $this->assertDatabaseMissing('stock_nudges', ['rule' => 'month_high', 'type' => 'market']);
        $dailyMessage = DB::table('stock_nudges')->where('rule', 'daily_gain')->value('message');
        $this->assertStringContainsString('₹110.00', $dailyMessage);
        $this->assertStringContainsString('₹10.00 (10.00%)', $dailyMessage);
        $this->assertStringContainsString('previous close of ₹100.00', $dailyMessage);
        $count = DB::table('stock_nudges')->count();

        $this->artisan('stock-nudges:generate market')->assertSuccessful();
        $this->assertSame($count, DB::table('stock_nudges')->count());

        Carbon::setTestNow(Carbon::parse('2026-10-10 10:00:00', 'Asia/Kolkata'));
        $this->updateQuote(110.50, 10.5);
        $this->artisan('stock-nudges:generate market')->assertSuccessful();
        $this->assertSame(1, DB::table('stock_nudges')->where('rule', 'year_high')->count());

        $this->updateQuote(112, 12);
        $this->artisan('stock-nudges:generate market')->assertSuccessful();
        $this->assertSame(2, DB::table('stock_nudges')->where('rule', 'year_high')->count());
    }

    public function test_market_nudges_use_five_percent_threshold_and_prefer_nse(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:15:00', 'Asia/Kolkata'));
        config(['stock_nudges.daily_move_percent' => 5]);
        $equity = Equity::create([
            'isin' => 'INE000000003',
            'company_name' => 'Dual Listed Limited',
            'nse_symbol' => 'DUAL',
            'bse_symbol' => '500003',
        ]);
        $nsePayload = json_encode(['dp' => 5, 'previous_close' => 100]);
        $bsePayload = json_encode(['dp' => -8, 'previous_close' => 100]);

        DB::table('equity_quotes')->insert([
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'symbol' => 'DUAL',
            'price' => 105,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'payload' => $nsePayload,
            'nse_symbol' => 'DUAL',
            'nse_price' => 105,
            'nse_quoted_at' => now()->utc(),
            'nse_fetched_at' => now()->utc(),
            'nse_payload' => $nsePayload,
            'bse_symbol' => '500003',
            'bse_price' => 92,
            'bse_quoted_at' => now()->utc(),
            'bse_fetched_at' => now()->utc(),
            'bse_payload' => $bsePayload,
        ]);

        $this->artisan('stock-nudges:generate market')->assertSuccessful();

        $this->assertDatabaseHas('stock_nudges', [
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'rule' => 'daily_gain',
        ]);
        $this->assertDatabaseMissing('stock_nudges', ['isin' => $equity->isin, 'exchange' => 'BSE']);
        $this->assertDatabaseMissing('stock_nudges', ['isin' => $equity->isin, 'rule' => 'daily_drop']);
    }

    public function test_stocks_use_nifty_and_sector_benchmarks_and_indices_use_nifty(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-14 12:15:00', 'Asia/Kolkata'));
        config(['stock_nudges.comparison_move_percent' => 5]);
        $equity = Equity::create([
            'isin' => 'INE000000004',
            'company_name' => 'Technology Leader Limited',
            'nse_symbol' => 'TECHLEAD',
            'sector' => 'Information Technology',
        ]);
        $payload = json_encode(['dp' => 8, 'previous_close' => 100]);
        DB::table('equity_quotes')->insert([
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'symbol' => 'TECHLEAD',
            'price' => 108,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'payload' => $payload,
            'nse_symbol' => 'TECHLEAD',
            'nse_price' => 108,
            'nse_quoted_at' => now()->utc(),
            'nse_fetched_at' => now()->utc(),
            'nse_payload' => $payload,
        ]);

        $this->indexQuote('NIFTY_50', 'Nifty 50', 'Broad-based', 2);
        $this->indexQuote('NIFTY_IT', 'Nifty IT', 'Sectoral', -1);
        $this->indexQuote('NIFTY_BANK', 'Nifty Bank', 'Sectoral', 9);

        $this->artisan('stock-nudges:generate market')->assertSuccessful();

        $this->assertSame(2, DB::table('stock_nudges')
            ->where('isin', $equity->isin)
            ->where('rule', 'index_outperformance')
            ->count());
        $this->assertDatabaseHas('stock_nudges', [
            'isin' => $equity->isin,
            'rule' => 'index_outperformance',
            'metrics->benchmark_index_code' => 'NIFTY_50',
        ]);
        $this->assertDatabaseHas('stock_nudges', [
            'isin' => $equity->isin,
            'rule' => 'index_outperformance',
            'metrics->benchmark_index_code' => 'NIFTY_IT',
        ]);
        $this->assertDatabaseHas('stock_nudges', [
            'index_code' => 'NIFTY_BANK',
            'rule' => 'index_outperformance',
            'metrics->benchmark_index_code' => 'NIFTY_50',
        ]);
    }

    public function test_eod_volume_spike_is_generated(): void
    {
        $equity = Equity::create(['isin' => 'INE000000002', 'company_name' => 'Volume Limited']);
        $this->price($equity->id, '2026-10-07', 100, 105, 95, 100, 1000);
        $this->price($equity->id, '2026-10-08', 100, 105, 95, 100, 1000);
        $this->price($equity->id, '2026-10-09', 100, 105, 95, 100, 3000);

        $this->artisan('stock-nudges:generate eod --date=2026-10-09')->assertSuccessful();

        $this->assertDatabaseHas('stock_nudges', [
            'isin' => $equity->isin,
            'type' => 'eod',
            'rule' => 'volume_spike',
        ]);
    }

    public function test_eod_nudges_prefer_nse_when_both_exchange_prices_exist(): void
    {
        config(['stock_nudges.daily_move_percent' => 5]);
        $equity = Equity::create([
            'isin' => 'INE000000005',
            'company_name' => 'Dual EOD Limited',
            'nse_symbol' => 'DUALEOD',
            'bse_symbol' => '500005',
        ]);
        DB::table('equity_prices')->insert([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'traded_date' => '2026-10-14',
            'nse_close' => 106,
            'nse_prev_close' => 100,
            'bse_close' => 90,
            'bse_prev_close' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('stock-nudges:generate eod --date=2026-10-14')->assertSuccessful();

        $this->assertDatabaseHas('stock_nudges', [
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'rule' => 'daily_gain',
        ]);
        $this->assertDatabaseMissing('stock_nudges', ['isin' => $equity->isin, 'exchange' => 'BSE']);
        $this->assertDatabaseMissing('stock_nudges', ['isin' => $equity->isin, 'rule' => 'daily_drop']);
    }

    public function test_market_and_eod_nudges_are_generated_for_indices(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'Asia/Kolkata'));
        DB::table('indices')->insert([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([
            ['2026-09-12', 100, 105, 95, 100, 1000],
            ['2026-10-12', 100, 105, 95, 100, 1000],
        ] as [$date, $open, $high, $low, $close, $volume]) {
            DB::table('indices_prices')->insert([
                'index_code' => 'NIFTY_50',
                'traded_date' => $date,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'close' => $close,
                'prev_close' => $open,
                'volume' => $volume,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('index_quotes')->insert([
            'index_code' => 'NIFTY_50',
            'price' => 112,
            'previous_close' => 100,
            'change' => 12,
            'change_percent' => 12,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('stock-nudges:generate market')->assertSuccessful();
        $this->assertDatabaseHas('stock_nudges', [
            'index_code' => 'NIFTY_50',
            'isin' => null,
            'rule' => 'daily_gain',
            'type' => 'market',
        ]);
        $this->assertDatabaseHas('stock_nudges', ['index_code' => 'NIFTY_50', 'rule' => 'year_high']);

        DB::table('indices_prices')->insert([
            'index_code' => 'NIFTY_50',
            'traded_date' => '2026-10-13',
            'open' => 100,
            'high' => 105,
            'low' => 95,
            'close' => 100,
            'prev_close' => 100,
            'volume' => 3000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->artisan('stock-nudges:generate eod --date=2026-10-13')->assertSuccessful();
        $this->assertDatabaseHas('stock_nudges', ['index_code' => 'NIFTY_50', 'rule' => 'volume_spike']);
    }

    private function price(int $equityId, string $date, float $open, float $high, float $low, float $close, int $volume): void
    {
        DB::table('equity_prices')->insert([
            'equity_id' => $equityId,
            'isin' => Equity::findOrFail($equityId)->isin,
            'traded_date' => $date,
            'nse_open' => $open,
            'nse_high' => $high,
            'nse_low' => $low,
            'nse_close' => $close,
            'nse_prev_close' => $open,
            'nse_volume' => $volume,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateQuote(float $price, float $changePercent): void
    {
        $payload = json_encode(['dp' => $changePercent, 'previous_close' => 100]);
        DB::table('equity_quotes')->update([
            'price' => $price,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'payload' => $payload,
            'nse_price' => $price,
            'nse_quoted_at' => now()->utc(),
            'nse_fetched_at' => now()->utc(),
            'nse_payload' => $payload,
        ]);
    }

    private function indexQuote(string $code, string $name, string $category, float $changePercent): void
    {
        DB::table('indices')->insert([
            'index_code' => $code,
            'index_name' => $name,
            'exchange' => 'NSE',
            'category' => $category,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('index_quotes')->insert([
            'index_code' => $code,
            'price' => 100 + $changePercent,
            'previous_close' => 100,
            'change' => $changePercent,
            'change_percent' => $changePercent,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
