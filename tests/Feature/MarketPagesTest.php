<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\EquityPrice;
use App\Models\MarketNews;
use App\Models\StockNudge;
use App\Services\EquityQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
