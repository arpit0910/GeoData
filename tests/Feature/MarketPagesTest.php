<?php

namespace Tests\Feature;

use App\Models\Equity;
use App\Models\EquityPrice;
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
            '/market/fundamentals',
            '/market/corporate-actions',
        ] as $uri) {
            $this->get($uri)->assertOk();
        }
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
}
