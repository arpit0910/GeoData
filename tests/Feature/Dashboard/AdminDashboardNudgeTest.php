<?php

namespace Tests\Feature\Dashboard;

use App\Models\Equity;
use App\Models\StockNudge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class AdminDashboardNudgeTest extends TestCase
{
    use CreatesTestData, RefreshDatabase;

    /** @test */
    public function admin_dashboard_shows_recent_nudges_in_indian_time(): void
    {
        $admin = $this->createAdminUser();
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Example Industries',
            'nse_symbol' => 'EXAMPLE',
        ]);

        StockNudge::create([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'type' => 'market',
            'rule' => 'daily_gain',
            'severity' => 'significant',
            'title' => 'Example Industries is up 5.25%',
            'message' => 'Example Industries crossed the configured threshold.',
            'observed_price' => 125.50,
            'trading_date' => '2026-10-14',
            'observed_at' => Carbon::parse('2026-10-14 12:00:00', 'Asia/Kolkata'),
            'fingerprint' => hash('sha256', 'admin-dashboard-nudge'),
        ]);

        $this->actingAs($admin)->view('dashboard')
            ->assertSee('Latest Market Nudges')
            ->assertSee('Example Industries is up 5.25%')
            ->assertSee('14 Oct 2026, 12:00 PM IST')
            ->assertSee('All dashboard times are shown in IST.')
            ->assertSee(route('admin.nudges.index'), false);
    }

    /** @test */
    public function admin_can_open_and_filter_the_nudges_page(): void
    {
        $admin = $this->createAdminUser();
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Example Industries',
            'nse_symbol' => 'EXAMPLE',
        ]);
        StockNudge::create([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'exchange' => 'NSE',
            'type' => 'market',
            'rule' => 'daily_gain',
            'severity' => 'significant',
            'title' => 'Today growth signal',
            'message' => 'Current trading-day signal.',
            'trading_date' => now('Asia/Kolkata')->toDateString(),
            'observed_at' => now('Asia/Kolkata'),
            'fingerprint' => hash('sha256', 'admin-nudges-page'),
        ]);

        $this->actingAs($admin)->get(route('admin.nudges.index', ['category' => 'growth']))
            ->assertOk()
            ->assertSee('Market Nudges')
            ->assertSee('Today growth signal')
            ->assertSee('Example Industries')
            ->assertSee('IST');
    }
}
