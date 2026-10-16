<?php

namespace Tests\Feature;

use App\Models\MutualFundNudge;
use App\Services\MutualFundNudgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MutualFundNudgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Carbon::setTestNow(Carbon::parse('2026-10-12 22:00:00', 'Asia/Kolkata'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_eod_mutual_fund_nudges_are_generated_once_without_index_comparisons(): void
    {
        $fundId = $this->fund('INF000000001', 'Growth Leaders Fund');
        foreach ([
            ['2025-10-12', 90, null, null],
            ['2026-09-12', 100, null, null],
            ['2026-10-12', 110, 10, 100],
        ] as [$date, $nav, $change, $previous]) {
            DB::table('mutual_fund_prices')->insert([
                'mf_id' => $fundId,
                'isin' => 'INF000000001',
                'nav_date' => $date,
                'nav' => $nav,
                'chg_1d' => $change,
                'val_1d' => $previous,
            ]);
        }

        $service = app(MutualFundNudgeService::class);
        $this->assertSame(2, $service->generateForDate('2026-10-12'));
        $this->assertSame(0, $service->generateForDate('2026-10-12'));
        $this->assertDatabaseHas('mutual_fund_nudges', [
            'isin' => 'INF000000001',
            'rule' => 'daily_gain',
            'nav_date' => '2026-10-12',
        ]);
        $this->assertDatabaseHas('mutual_fund_nudges', ['rule' => 'year_high']);
        $this->assertSame(0, MutualFundNudge::where('rule', 'like', 'index_%')->count());
    }

    public function test_mutual_fund_nudge_api_returns_only_the_last_seven_days_by_default(): void
    {
        $fundId = $this->fund('INF000000002', 'API Growth Fund');
        foreach (['2026-10-12', '2026-10-06', '2026-10-05'] as $date) {
            MutualFundNudge::create([
                'mutual_fund_id' => $fundId,
                'isin' => 'INF000000002',
                'rule' => 'daily_gain',
                'severity' => 'significant',
                'title' => 'NAV gained',
                'message' => 'NAV gained today.',
                'observed_nav' => 110,
                'metrics' => ['change_percent' => 10],
                'nav_date' => $date,
                'observed_at' => Carbon::parse("{$date} 22:00:00", 'Asia/Kolkata'),
                'fingerprint' => hash('sha256', $date),
            ]);
        }

        $this->getJson('/api/v1/mf/nudges')
            ->assertOk()
            ->assertJsonPath('date_from', '2026-10-06')
            ->assertJsonPath('date_to', '2026-10-12')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.scheme_name', 'API Growth Fund')
            ->assertJsonPath('data.0.category', 'growth');

        $this->getJson('/api/v1/mf/nudges?date=2026-10-05')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.nav_date', '2026-10-05');
    }

    private function fund(string $isin, string $name): int
    {
        return DB::table('mutual_funds')->insertGetId([
            'isin' => $isin,
            'scheme_code' => substr($isin, -6),
            'scheme_name' => $name,
            'amc_name' => 'Example AMC',
            'category' => 'Equity',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
