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
