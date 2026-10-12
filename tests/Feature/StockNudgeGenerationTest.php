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
}
