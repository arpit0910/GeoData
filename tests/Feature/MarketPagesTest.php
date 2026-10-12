<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\EquityPrice;
use App\Models\MarketNews;
use App\Models\StockNudge;
use App\Models\Index as MarketIndex;
use App\Models\IndexPrice;
use App\Models\IndexQuote;
use App\Services\EquityQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MarketPagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_public_market_sections_load_independently(): void
    {
        foreach ([
            '/market',
            '/market/stocks',
            '/market/indices',
            '/market/mutual-funds',
            '/market/news',
            '/market/nudges',
            '/market/fundamentals',
            '/market/corporate-actions',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    /** @test */
    public function market_overview_displays_live_first_index_prices_without_a_compare_page(): void
    {
        $nifty = MarketIndex::create([
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
        ]);
        IndexPrice::create([
            'index_code' => $nifty->index_code,
            'traded_date' => now('Asia/Kolkata')->subDay()->toDateString(),
            'close' => 25000,
            'prev_close' => 24900,
            'change_percent' => 0.4,
        ]);
        IndexQuote::create([
            'index_code' => $nifty->index_code,
            'price' => 25125.5,
            'previous_close' => 25000,
            'change' => 125.5,
            'change_percent' => 0.502,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
        ]);

        $sensex = MarketIndex::create([
            'index_code' => 'BSE_SENSEX',
            'index_name' => 'S&P BSE SENSEX',
            'exchange' => 'BSE',
        ]);
        IndexPrice::create([
            'index_code' => $sensex->index_code,
            'traded_date' => now('Asia/Kolkata')->subDay()->toDateString(),
            'close' => 81000,
            'prev_close' => 80000,
            'change_percent' => 1.25,
        ]);

        Cache::forget('website.market.indices.v2');
        $this->get('/market')
            ->assertOk()
            ->assertSee('Market indices')
            ->assertSee('Nifty 50')
            ->assertSee('25,125.50')
            ->assertSee('Live')
            ->assertSee('Growth signal')
            ->assertSee(route('market.indices.show', 'NIFTY_50'), false)
            ->assertSee('S&amp;P BSE SENSEX', false)
            ->assertSee('81,000.00')
            ->assertSee('EOD')
            ->assertDontSee('Stock vs index');

        $this->get(route('market.indices'))
            ->assertOk()
            ->assertSee('Nifty 50')
            ->assertSee('Growth signal');

        $this->get(route('market.indices.show', $nifty))
            ->assertOk()
            ->assertSee('Nifty 50')
            ->assertSee('Growth signal')
            ->assertSee('Recent performance');

        IndexQuote::create([
            'index_code' => $sensex->index_code,
            'price' => 79500,
            'previous_close' => 80000,
            'change' => -500,
            'change_percent' => -0.625,
            'quoted_at' => now()->utc(),
            'fetched_at' => now()->utc(),
        ]);
        $this->get(route('market.indices', ['signal' => 'fall']))
            ->assertOk()
            ->assertSee('S&amp;P BSE SENSEX', false)
            ->assertSee('Fall signal')
            ->assertDontSee('Nifty 50');

        $this->get('/market/compare')->assertNotFound();
    }

    /** @test */
    public function nudges_page_lists_and_filters_market_signals(): void
    {
        $equity = Equity::create([
            'isin' => 'INE000A01001',
            'company_name' => 'Nudge Example Limited',
            'nse_symbol' => 'NUDGE',
            'sector' => 'Technology',
            'market_cap_category' => 'Large Cap',
            'series' => 'EQ',
            'is_active' => true,
        ]);

        $otherEquity = Equity::create([
            'isin' => 'INE000A01002',
            'company_name' => 'Other Nudge Limited',
            'nse_symbol' => 'OTHER',
            'sector' => 'Healthcare',
            'market_cap_category' => 'Mid Cap',
            'series' => 'EQ',
            'is_active' => true,
        ]);

        foreach ([
            ['equity' => $equity, 'rule' => 'daily_gain', 'title' => 'Growth signal', 'fingerprint' => str_repeat('a', 64)],
            ['equity' => $otherEquity, 'rule' => 'daily_drop', 'title' => 'Fall signal', 'fingerprint' => str_repeat('b', 64)],
        ] as $signal) {
            StockNudge::create([
                'equity_id' => $signal['equity']->id,
                'isin' => $signal['equity']->isin,
                'exchange' => 'NSE',
                'type' => 'price',
                'rule' => $signal['rule'],
                'title' => $signal['title'],
                'fingerprint' => $signal['fingerprint'],
                'severity' => 'info',
                'message' => 'A market movement was detected.',
                'observed_price' => 125.50,
                'trading_date' => now('Asia/Kolkata')->toDateString(),
                'observed_at' => now(),
            ]);
        }

        $this->get(route('market.nudges'))
            ->assertOk()
            ->assertSee('Growth signal')
            ->assertSee('Fall signal')
            ->assertSee('Nudge Example Limited')
            ->assertSee('Technology')
            ->assertSee('Large Cap');

        $this->get(route('market.nudges', ['category' => 'growth']))
            ->assertOk()
            ->assertSee('Growth signal')
            ->assertDontSee('Fall signal');

        $this->get(route('market.nudges', ['sector' => 'Technology', 'stock_category' => 'Large Cap']))
            ->assertOk()
            ->assertSee('Growth signal')
            ->assertDontSee('Fall signal');
    }

    /** @test */
    public function stock_change_uses_the_previous_close_from_the_same_live_quote(): void
    {
        $equity = Equity::create([
            'isin' => 'INE000A01001',
            'company_name' => 'Quote Match Limited',
            'nse_symbol' => 'QUOTEMATCH',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        EquityPrice::create([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'traded_date' => now('Asia/Kolkata')->subDay()->toDateString(),
            'nse_close' => 55,
            'nse_prev_close' => 50,
            'nse_high' => 60,
            'nse_low' => 45,
            'nse_volume' => 500,
        ]);
        app(EquityQuoteService::class)->store($equity->isin, 'NSE', [
            'symbol' => 'QUOTEMATCH',
            'price' => 110,
            'previous_close' => 100,
            'd' => 10,
            'dp' => 10,
            'volume' => 1000,
            'quoted_at' => now()->utc()->toIso8601String(),
            'fetched_at' => now()->utc()->toIso8601String(),
            'source' => 'live',
            'market_data' => ['volume' => 1000],
        ]);

        $this->get('/market/stocks')
            ->assertOk()
            ->assertSee('QUOTEMATCH')
            ->assertSee('+10.00%')
            ->assertSee('1,000')
            ->assertSee('60.00')
            ->assertSee(now('Asia/Kolkata')->subDay()->format('d M'));
    }

    /** @test */
    public function stock_filters_can_be_combined_and_are_exposed_on_the_page(): void
    {
        Equity::create([
            'isin' => 'INE000A01011',
            'company_name' => 'Alpha Technology Limited',
            'nse_symbol' => 'ALPHATECH',
            'sector' => 'Technology',
            'market_cap_category' => 'Large Cap',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        Equity::create([
            'isin' => 'INE000A01012',
            'company_name' => 'Beta Finance Limited',
            'bse_symbol' => 'BETAFIN',
            'sector' => 'Financial Services',
            'market_cap_category' => 'Mid Cap',
            'series' => 'EQ',
            'is_active' => true,
        ]);

        $this->get(route('market.stocks', [
            'filter' => 'nse',
            'sector' => 'Technology',
            'stock_category' => 'Large Cap',
            'sort' => 'name_asc',
        ]))
            ->assertOk()
            ->assertSee('Alpha Technology Limited')
            ->assertDontSee('Beta Finance Limited')
            ->assertSee('name="filter"', false)
            ->assertSee('name="sector"', false)
            ->assertSee('name="stock_category"', false)
            ->assertSee('market-mobile-cards', false)
            ->assertSee('data-label="Latest price"', false);
    }

    /** @test */
    public function mutual_fund_filters_and_sorting_work_together(): void
    {
        foreach ([
            ['isin' => 'INF000000011', 'scheme_code' => '11', 'scheme_name' => 'Alpha Growth Fund', 'amc_name' => 'Alpha AMC', 'category' => 'Equity'],
            ['isin' => 'INF000000012', 'scheme_code' => '12', 'scheme_name' => 'Zeta Growth Fund', 'amc_name' => 'Alpha AMC', 'category' => 'Equity'],
            ['isin' => 'INF000000013', 'scheme_code' => '13', 'scheme_name' => 'Beta Debt Fund', 'amc_name' => 'Beta AMC', 'category' => 'Debt'],
        ] as $fund) {
            DB::table('mutual_funds')->insert($fund + [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->get(route('market.mutual-funds', [
            'category' => 'Equity',
            'amc' => 'Alpha AMC',
            'sort' => 'name_desc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Zeta Growth Fund', 'Alpha Growth Fund'])
            ->assertDontSee('Beta Debt Fund')
            ->assertSee('name="amc"', false)
            ->assertSee('name="sort"', false);
    }

    /** @test */
    public function news_listing_shows_a_preview_and_links_to_a_published_detail_page(): void
    {
        $summary = str_repeat('Verified market information provides context for investors and readers. ', 8).
            'FULL_ARTICLE_END_MARKER';
        $published = MarketNews::create([
            'title' => 'Published company update with complete verified details',
            'summary' => $summary,
            'symbol' => 'NEWSCO',
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
            'published_at' => now(),
        ]);
        $private = MarketNews::create([
            'title' => 'Private pending company update',
            'summary' => 'This story is not available publicly.',
            'editorial_status' => MarketNews::STATUS_PENDING,
            'is_published' => false,
            'published_at' => now(),
        ]);

        $this->get(route('market.news'))
            ->assertOk()
            ->assertSee('Published company update with complete verified details')
            ->assertSee('View details')
            ->assertSee(route('market.news.show', $published), false)
            ->assertDontSee('FULL_ARTICLE_END_MARKER')
            ->assertDontSee('Private pending company update');

        cache()->forget('website.market.overview');
        $this->get(route('market.index'))
            ->assertOk()
            ->assertSee('Published company update with complete verified details')
            ->assertSee('View details')
            ->assertSee(route('market.news.show', $published), false)
            ->assertDontSee('FULL_ARTICLE_END_MARKER')
            ->assertDontSee('Private pending company update');

        $this->get(route('market.news.show', $published))
            ->assertOk()
            ->assertSee('FULL_ARTICLE_END_MARKER')
            ->assertSee('Back to market news');

        $this->get(route('market.news.show', $private))->assertNotFound();
    }
}
