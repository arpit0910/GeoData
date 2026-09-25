<?php

namespace Tests\Feature\Api;

use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
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
}
