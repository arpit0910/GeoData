<?php

namespace Tests\Feature;

use App\Models\Index;
use App\Models\IndexPrice;
use App\Models\User;
use App\Console\Commands\SyncIndices;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_store_the_correct_previous_trading_close_and_returns(): void
    {
        Index::create([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'category' => 'Broad Market',
        ]);

        foreach ([
            ['2026-08-28', 100.00],
            ['2026-10-02', 110.00],
            ['2026-10-05', 121.00],
        ] as [$date, $close]) {
            IndexPrice::create([
                'index_code' => 'NIFTY_50',
                'traded_date' => $date,
                'open' => $close,
                'high' => $close,
                'low' => $close,
                'close' => $close,
            ]);
        }

        app(SyncIndices::class)->calculateAnalytics(Carbon::parse('2026-10-05'));

        $price = IndexPrice::where('index_code', 'NIFTY_50')
            ->whereDate('traded_date', '2026-10-05')
            ->firstOrFail();

        $this->assertEquals(110.00, (float) $price->prev_close);
        $this->assertEquals(110.00, (float) $price->val_1d);
        $this->assertEquals(10.00, (float) $price->chg_1d);
        $this->assertEquals(100.00, (float) $price->val_1m);
        $this->assertEquals(21.00, (float) $price->chg_1m);
    }

    public function test_index_pages_show_latest_available_holdings_and_news_navigation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $index = Index::create([
            'index_code' => 'NIFTY_500',
            'index_name' => 'Nifty 500',
            'exchange' => 'NSE',
            'category' => 'Broad Market',
        ]);

        IndexPrice::create([
            'index_code' => $index->index_code,
            'traded_date' => '2026-10-02',
            'close' => 100,
            'holdings' => [[
                'company_name' => 'Reliance Industries',
                'symbol' => 'RELIANCE',
                'weightage_percentage' => 8.5,
            ]],
        ]);
        $latest = IndexPrice::create([
            'index_code' => $index->index_code,
            'traded_date' => '2026-10-05',
            'close' => 101,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.indices.show', $index))
            ->assertOk()
            ->assertSee('Index Holdings')
            ->assertSee('Reliance Industries')
            ->assertSee(route('market.news'), false)
            ->assertSee('News');

        $this->actingAs($admin)
            ->getJson(route('admin.indices.prices.show', $latest))
            ->assertOk()
            ->assertJsonPath('holdings.0.symbol', 'RELIANCE')
            ->assertJsonPath('holdings_as_of', '2026-10-02');
    }
}
