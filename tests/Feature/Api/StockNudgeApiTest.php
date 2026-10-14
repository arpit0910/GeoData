<?php

namespace Tests\Feature\Api;

use App\Models\StockNudge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockNudgeApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        Carbon::setTestNow(Carbon::parse('2026-10-12 10:00:00', 'Asia/Kolkata'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_nudges_can_be_fetched_by_isin(): void
    {
        $this->nudge('INE000000001', 'daily_gain', '2026-10-12');
        $this->nudge('INE000000002', 'daily_drop', '2026-10-12');

        $this->getJson('/api/v1/nudges/isin/ine000000001')
            ->assertOk()
            ->assertJsonPath('isin', 'INE000000001')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.category', 'growth')
            ->assertJsonPath('data.0.isin', 'INE000000001')
            ->assertJsonPath('data.0.observed_at', '2026-10-12T15:30:00+05:30');
    }

    public function test_fresh_nudges_only_include_todays_trading_date(): void
    {
        $this->nudge('INE000000001', 'daily_gain', '2026-10-12');
        $this->nudge('INE000000002', 'daily_drop', '2026-10-11');

        $this->getJson('/api/v1/nudges/fresh')
            ->assertOk()
            ->assertJsonPath('date', '2026-10-12')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.isin', 'INE000000001');
    }

    public function test_nudges_can_be_fetched_by_category(): void
    {
        $this->nudge('INE000000001', 'daily_drop', '2026-10-12');
        $this->nudge('INE000000002', 'year_low', '2026-10-11');
        $this->nudge('INE000000003', 'daily_gain', '2026-10-12');

        $this->getJson('/api/v1/nudges/category/fall')
            ->assertOk()
            ->assertJsonPath('category', 'fall')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.category', 'fall')
            ->assertJsonPath('data.1.category', 'fall');

        $this->getJson('/api/v1/nudges/category/unknown')
            ->assertStatus(422)
            ->assertJsonPath('allowed_categories.0', 'growth');
    }

    public function test_invalid_isin_is_rejected(): void
    {
        $this->getJson('/api/v1/nudges/isin/bad')->assertStatus(422);
    }

    public function test_index_nudges_can_be_fetched_by_index_code(): void
    {
        DB::table('indices')->insert([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        StockNudge::create([
            'index_code' => 'NIFTY_50',
            'exchange' => 'NSE',
            'type' => 'market',
            'rule' => 'daily_gain',
            'severity' => 'significant',
            'title' => 'Nifty 50 is up 10%',
            'message' => 'Index nudge',
            'observed_price' => 25000,
            'trading_date' => '2026-10-12',
            'observed_at' => now(),
            'fingerprint' => hash('sha256', 'NIFTY_50|daily_gain|2026-10-12'),
        ]);

        $this->getJson('/api/v1/nudges/index/nifty_50')
            ->assertOk()
            ->assertJsonPath('index_code', 'NIFTY_50')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.instrument_type', 'index')
            ->assertJsonPath('data.0.index_code', 'NIFTY_50');
    }

    public function test_stock_comparison_nudges_can_be_fetched_by_benchmark_index(): void
    {
        StockNudge::create([
            'isin' => 'INE000000006',
            'exchange' => 'NSE',
            'type' => 'market',
            'rule' => 'index_outperformance',
            'severity' => 'notable',
            'title' => 'Stock outperformed Nifty 50',
            'message' => 'Comparison nudge',
            'observed_price' => 105,
            'metrics' => ['benchmark_index_code' => 'NIFTY_50'],
            'trading_date' => '2026-10-12',
            'observed_at' => now(),
            'fingerprint' => hash('sha256', 'INE000000006|NIFTY_50|2026-10-12'),
        ]);

        $this->getJson('/api/v1/nudges/index/nifty_50')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.instrument_type', 'stock')
            ->assertJsonPath('data.0.category', 'comparison')
            ->assertJsonPath('data.0.metrics.benchmark_index_code', 'NIFTY_50');
    }

    private function nudge(string $isin, string $rule, string $date): StockNudge
    {
        return StockNudge::create([
            'isin' => $isin,
            'exchange' => 'NSE',
            'type' => 'eod',
            'rule' => $rule,
            'severity' => 'significant',
            'title' => "{$rule} nudge",
            'message' => "{$rule} message",
            'observed_price' => 100,
            'metrics' => ['change_percent' => 10],
            'trading_date' => $date,
            'observed_at' => Carbon::parse("{$date} 15:30:00", 'Asia/Kolkata'),
            'fingerprint' => hash('sha256', "{$isin}|{$rule}|{$date}"),
        ]);
    }
}
