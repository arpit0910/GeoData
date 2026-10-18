<?php

namespace Tests\Feature\Api;

use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Models\InstitutionalActivity;
use App\Models\Ipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketDatasetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_global_instruments_are_paginated_without_internal_provider_data(): void
    {
        GlobalInstrument::create([
            'instrument_key' => 'INTERNAL|TEST',
            'segment' => 'GLOBAL',
            'name' => 'Test Global Index',
            'exchange' => 'GLOBAL',
            'country' => 'India',
            'instrument_type' => 'INDEX',
            'trading_symbol' => 'TESTINDEX',
            'is_active' => true,
            'provider_payload' => ['provider' => 'internal'],
            'synced_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/market/global-instruments?country=India');

        $response->assertOk()
            ->assertJsonPath('data.0.name', 'Test Global Index')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonMissingPath('data.0.instrument_key')
            ->assertJsonMissingPath('data.0.provider_payload');
    }

    public function test_complete_fundamentals_support_filters_and_sanitize_internal_fields(): void
    {
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries Limited',
            'nse_symbol' => 'RELIANCE',
            'series' => 'EQ',
            'is_active' => true,
        ]);
        CompanyFundamental::create([
            'equity_id' => $equity->id,
            'isin' => $equity->isin,
            'dataset' => 'profile',
            'statement_type' => 'not_applicable',
            'time_period' => 'not_applicable',
            'payload' => [
                'company_name' => 'Reliance Industries Limited',
                'instrument_key' => 'NSE_EQ|internal',
                'provider' => 'Upstox',
                'description' => 'Sourced through Upstox infrastructure',
            ],
            'synced_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/market/company-fundamentals/INE002A01018?dataset=profile');

        $response->assertOk()
            ->assertJsonPath('company.nse_symbol', 'RELIANCE')
            ->assertJsonPath('data.0.dataset', 'profile')
            ->assertJsonPath('data.0.payload.company_name', 'Reliance Industries Limited')
            ->assertJsonMissingPath('data.0.payload.instrument_key')
            ->assertJsonMissingPath('data.0.payload.provider');
        $this->assertStringNotContainsStringIgnoringCase('upstox', $response->getContent());
    }

    public function test_fundamentals_reject_invalid_isin_and_dataset(): void
    {
        $this->getJson('/api/v1/market/company-fundamentals/bad')->assertStatus(422);
        $this->getJson('/api/v1/market/company-fundamentals/INE002A01018?dataset=unknown')->assertStatus(422);
    }

    public function test_ipo_list_and_details_are_available(): void
    {
        Ipo::create([
            'slug' => 'example-ipo',
            'symbol' => 'EXAMPLE',
            'name' => 'Example IPO',
            'status' => 'open',
            'issue_type' => 'regular',
            'minimum_price' => 100,
            'maximum_price' => 110,
            'total_subscription' => 2.5,
            'details' => ['lot_size' => 100, 'timeline' => ['listing_date' => '2026-10-26']],
            'synced_at' => now(),
        ]);

        $this->getJson('/api/v1/market/ipos?status=open')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'example-ipo')
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonMissingPath('data.0.details');

        $this->getJson('/api/v1/market/ipos/example-ipo')
            ->assertOk()
            ->assertJsonPath('data.details.lot_size', 100)
            ->assertJsonPath('data.details.timeline.listing_date', '2026-10-26');

        $this->getJson('/api/v1/market/ipos?status=invalid')->assertStatus(422);
    }

    public function test_institutional_activity_supports_filters_and_calculates_net_amount(): void
    {
        InstitutionalActivity::create([
            'investor_type' => 'fii',
            'data_type' => 'NSE_EQ|CASH',
            'interval' => '1D',
            'activity_at' => '2026-10-16 00:00:00',
            'buy_amount' => 1500,
            'sell_amount' => 1200,
            'synced_at' => now(),
        ]);

        $this->getJson('/api/v1/market/institutional-activity?investor_type=fii&interval=1D')
            ->assertOk()
            ->assertJsonPath('data.0.investor_type', 'FII')
            ->assertJsonPath('data.0.net_amount', 300)
            ->assertJsonPath('pagination.total', 1);

        $this->getJson('/api/v1/market/institutional-activity?investor_type=invalid')->assertStatus(422);
        $this->getJson('/api/v1/market/institutional-activity?from=17-10-2026')->assertStatus(422);
    }
}
