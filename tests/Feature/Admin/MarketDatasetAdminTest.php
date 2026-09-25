<?php

namespace Tests\Feature\Admin;

use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketDatasetAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_global_instruments_and_company_fundamentals(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        GlobalInstrument::create([
            'instrument_key' => 'GLOBAL|TEST',
            'segment' => 'GLOBAL',
            'name' => 'Test Global Index',
            'exchange' => 'GLOBAL',
            'country' => 'India',
            'is_active' => true,
            'synced_at' => now(),
        ]);
        $equity = Equity::create([
            'isin' => 'INE000000001',
            'company_name' => 'Test Company Limited',
            'nse_symbol' => 'TESTCO',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        $fundamental = CompanyFundamental::create([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'dataset' => 'profile',
            'statement_type' => 'not_applicable',
            'time_period' => 'not_applicable',
            'payload' => ['name' => 'Test Company Limited'],
            'synced_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.market-datasets.global-instruments'))
            ->assertOk()->assertSee('Test Global Index');
        $this->actingAs($admin)->get(route('admin.market-datasets.company-fundamentals'))
            ->assertOk()->assertSee('Test Company Limited')->assertSee('Historical / Old Data Sync');
        $this->actingAs($admin)->get(route('admin.market-datasets.company-fundamentals.show', $fundamental))
            ->assertOk()->assertSee('Stored Payload')->assertSee('Test Company Limited');
    }

    public function test_non_admin_cannot_view_market_dataset_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.market-datasets.global-instruments'))->assertRedirect('/');
        $this->actingAs($user)->get(route('admin.market-datasets.company-fundamentals'))->assertRedirect('/');
    }
}
