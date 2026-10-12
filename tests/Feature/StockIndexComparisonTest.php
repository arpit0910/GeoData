<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\Index as MarketIndex;
use App\Services\StockIndexComparisonService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockIndexComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_compares_returns_on_shared_trading_dates_only(): void
    {
        Carbon::setTestNow('2026-10-12 12:00:00 Asia/Kolkata');
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'is_active' => true,
        ]);
        $index = MarketIndex::create([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
        ]);

        DB::table('equity_prices')->insert([
            ['equity_id' => $equity->id, 'isin' => $equity->isin, 'traded_date' => '2026-09-01', 'nse_close' => 100, 'created_at' => now(), 'updated_at' => now()],
            ['equity_id' => $equity->id, 'isin' => $equity->isin, 'traded_date' => '2026-09-02', 'nse_close' => 110, 'created_at' => now(), 'updated_at' => now()],
            ['equity_id' => $equity->id, 'isin' => $equity->isin, 'traded_date' => '2026-09-03', 'nse_close' => 120, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('indices_prices')->insert([
            ['index_code' => $index->index_code, 'traded_date' => '2026-09-01', 'close' => 200, 'created_at' => now(), 'updated_at' => now()],
            // No 2 September row: it must not be matched to another date.
            ['index_code' => $index->index_code, 'traded_date' => '2026-09-03', 'close' => 220, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $result = app(StockIndexComparisonService::class)->compare($equity, $index, '3m');

        $this->assertSame(2, $result['summary']['aligned_points']);
        $this->assertSame(20.0, $result['summary']['stock_return']);
        $this->assertSame(10.0, $result['summary']['index_return']);
        $this->assertSame(10.0, $result['summary']['outperformance']);
        $this->assertSame(['2026-09-01', '2026-09-03'], array_column($result['data'], 'date'));

        $this->get('/market/compare?stock=RELIANCE&index_code=NIFTY_50&period=3m')
            ->assertOk()
            ->assertSee('Outperformance')
            ->assertSee('+10.00 pp');
    }
}
