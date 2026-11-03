<?php

namespace Tests\Feature;

use App\Models\InstitutionalActivity;
use App\Models\Ipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class MarketInsightsPagesTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    /** @test */
    public function public_market_page_shows_ipo_and_institutional_activity(): void
    {
        $this->seedInsights();
        cache()->forget('website.market.overview');

        $this->get(route('market.index'))
            ->assertOk()
            ->assertSee('Example Industries IPO')
            ->assertSee('IPOs and FII/DII activity')
            ->assertSee('FII')
            ->assertSee('+250.00');
    }

    /** @test */
    public function admin_can_view_insights_and_sync_controls(): void
    {
        $this->seedInsights();
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->get(route('admin.market-datasets.insights'))
            ->assertOk()
            ->assertSee('FII/DII Activity')
            ->assertSee('Example Industries IPO')
            ->assertSee('market:sync-upstox-insights');

        $this->actingAs($admin)->view('dashboard')
            ->assertSee('Market data sync')
            ->assertSee('Latest IPOs')
            ->assertSee('Example Industries IPO');
    }

    public function test_ipo_calendar_displays_all_stored_details_and_escapes_provider_content(): void
    {
        $this->seedInsights();
        Ipo::firstOrFail()->update([
            'isin' => 'INE000000001', 'industry' => 'Manufacturing', 'issue_size' => 500,
            'total_subscription' => 2.5,
            'details' => [
                'lot_size' => 100,
                'timeline' => ['allotment_date' => '2026-10-22', 'listing_date' => '2026-10-26'],
                'subscription' => ['retail' => 1.25, 'qib' => 3.5],
                'documents' => ['prospectus' => 'https://example.test/prospectus.pdf'],
                'purchase_allowed' => false, 'reserved_shares' => 0,
                'description' => '<script>alert("provider")</script>',
            ],
        ]);
        cache()->forget('website.market.overview');
        $this->get(route('market.index'))->assertOk()
            ->assertSee('INE000000001')->assertSee('Manufacturing')->assertSee('500')
            ->assertSee('Lot size')->assertSee('100')->assertSee('2.50')
            ->assertSee('Allotment Date')->assertSee('2026-10-22')->assertSee('2026-10-26')
            ->assertSee('Retail')->assertSee('1.25')->assertSee('https://example.test/prospectus.pdf')
            ->assertSee('No')->assertSee('Reserved Shares')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("provider")</script>', false);
    }

    private function seedInsights(): void
    {
        Ipo::create([
            'slug' => 'example-industries-ipo',
            'symbol' => 'EXAMPLE',
            'name' => 'Example Industries IPO',
            'status' => 'open',
            'minimum_price' => 100,
            'maximum_price' => 110,
            'bidding_start_date' => '2026-10-18',
            'bidding_end_date' => '2026-10-20',
            'synced_at' => now(),
        ]);

        InstitutionalActivity::create([
            'investor_type' => 'fii',
            'data_type' => 'NSE_EQ|CASH',
            'interval' => '1D',
            'activity_at' => now(),
            'buy_amount' => 1250,
            'sell_amount' => 1000,
            'synced_at' => now(),
        ]);
    }
}
