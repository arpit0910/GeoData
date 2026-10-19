<?php

namespace Tests\Feature\Api;

use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DateAwareDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    /** @test */
    public function stock_details_use_requested_date_or_latest_available_date(): void
    {
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Example Industries',
            'nse_symbol' => 'EXAMPLE',
            'logo_url' => 'https://setugeo.test/assets/equity-icons/INE000000001.svg',
        ]);
        foreach ([
            ['2026-06-12', 80],
            ['2026-06-13', 90],
            ['2026-10-10', 100],
            ['2026-10-13', 125],
        ] as [$date, $close]) {
            EquityPrice::create([
                'equity_id' => $equity->id,
                'isin' => $equity->isin,
                'traded_date' => $date,
                'nse_close' => $close,
            ]);
        }

        $this->getJson('/api/v1/equity/INE000000001')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-13')
            ->assertJsonPath('data.logo_url', 'https://setugeo.test/assets/equity-icons/INE000000001.svg')
            ->assertJsonPath('data.price.nse_close', '125.00')
            ->assertJsonCount(3, 'data.price_history')
            ->assertJsonPath('data.price_history.0.traded_date', '2026-06-13')
            ->assertJsonPath('data.price_history.2.traded_date', '2026-10-13');

        $this->getJson('/api/v1/equity/INE000000001?date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-10')
            ->assertJsonPath('data.price.nse_close', '100.00')
            ->assertJsonCount(3, 'data.price_history')
            ->assertJsonPath('data.price_history.0.traded_date', '2026-06-12')
            ->assertJsonPath('data.price_history.2.traded_date', '2026-10-10');

        $this->getJson('/api/v1/equity/INE000000001?date=2026-10-11')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-10')
            ->assertJsonPath('data.price.nse_close', '100.00');
        $this->getJson('/api/v1/equity/INE000000001?date=2026-06-11')->assertNotFound();
        $this->getJson('/api/v1/equity/INE000000001?date=10-10-2026')->assertStatus(422);
    }

    /** @test */
    public function mutual_fund_details_use_requested_date_or_latest_available_date(): void
    {
        $fundId = DB::table('mutual_funds')->insertGetId([
            'isin' => 'INF000000001',
            'scheme_code' => '123456',
            'scheme_name' => 'Example Growth Fund',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([['2026-10-10', 20, 1.5], ['2026-10-13', 24, 2.5]] as [$date, $nav, $change]) {
            DB::table('mutual_fund_prices')->insert([
                'mf_id' => $fundId,
                'isin' => 'INF000000001',
                'nav_date' => $date,
                'nav' => $nav,
                'chg_1d' => $change,
            ]);
        }

        $this->getJson('/api/v1/mf/details/INF000000001')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-13')
            ->assertJsonPath('data.latest_nav', 24);

        $this->getJson('/api/v1/mf/details/INF000000001?date=2026-10-10')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-10')
            ->assertJsonPath('data.latest_nav', 20)
            ->assertJsonPath('data.nav_history.last_date', '2026-10-10');

        $this->getJson('/api/v1/mf/details/INF000000001?date=2026-10-11')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-10')
            ->assertJsonPath('data.latest_nav', 20);
        $this->getJson('/api/v1/mf/details/INF000000001?date=2026-10-09')->assertNotFound();
        $this->getJson('/api/v1/mf/details/INF000000001?date=10-10-2026')->assertStatus(422);
    }

    /** @test */
    public function unified_stock_details_include_recent_daily_eod_history(): void
    {
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Example Industries',
            'nse_symbol' => 'EXAMPLE',
            'logo_url' => 'https://setugeo.test/assets/equity-icons/INE000000001.svg',
        ]);
        foreach ([['2026-10-10', 100], ['2026-10-13', 125]] as [$date, $close]) {
            EquityPrice::create([
                'equity_id' => $equity->id,
                'isin' => $equity->isin,
                'traded_date' => $date,
                'nse_open' => $close - 1,
                'nse_high' => $close + 2,
                'nse_low' => $close - 3,
                'nse_close' => $close,
                'nse_volume' => 1000,
            ]);
        }

        $this->getJson('/api/v1/market/isin/INE000000001')
            ->assertOk()
            ->assertJsonPath('data.company.logo_url', 'https://setugeo.test/assets/equity-icons/INE000000001.svg')
            ->assertJsonCount(2, 'data.price_history')
            ->assertJsonPath('data.price_history.0.traded_date', '2026-10-13')
            ->assertJsonPath('data.price_history.0.nse_close', 125)
            ->assertJsonPath('data.price_history.1.traded_date', '2026-10-10');
    }
}
