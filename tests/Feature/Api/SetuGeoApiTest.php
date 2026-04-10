<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetuGeoApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $geoData;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authData = $this->createAuthenticatedApiUser();
        $this->geoData = $this->createGeoHierarchy();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    // ─── REGIONS ───────────────────────────────────────────────────

    /** @test */
    public function it_fetches_regions()
    {
        $response = $this->getJson('/api/v1/regions', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'total'],
            ])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_filters_regions_by_name()
    {
        $response = $this->getJson('/api/v1/regions?name=Asia', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertStringContainsString('Asia', $data[0]['name']);
    }

    // ─── SUB-REGIONS ───────────────────────────────────────────────

    /** @test */
    public function it_fetches_subregions()
    {
        $response = $this->getJson('/api/v1/sub-regions', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_filters_subregions_by_region_id()
    {
        $response = $this->getJson('/api/v1/sub-regions?region_id=' . $this->geoData['region']->id, $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    // ─── TIMEZONES ─────────────────────────────────────────────────

    /** @test */
    public function it_fetches_timezones()
    {
        $response = $this->getJson('/api/v1/timezones', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_filters_timezones_by_zone_name()
    {
        $response = $this->getJson('/api/v1/timezones?zone_name=Kolkata', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    // ─── COUNTRIES ─────────────────────────────────────────────────

    /** @test */
    public function it_fetches_countries()
    {
        $response = $this->getJson('/api/v1/countries', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'name', 'iso2', 'iso3']],
                'meta',
            ]);
    }

    /** @test */
    public function it_filters_countries_by_name()
    {
        $response = $this->getJson('/api/v1/countries?name=India', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('India', $data[0]['name']);
    }

    /** @test */
    public function it_filters_countries_by_iso2()
    {
        $response = $this->getJson('/api/v1/countries?iso2=IN', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('IN', $data[0]['iso2']);
    }

    /** @test */
    public function it_filters_countries_by_iso3()
    {
        $response = $this->getJson('/api/v1/countries?iso3=IND', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function it_gets_country_detail()
    {
        $response = $this->getJson('/api/v1/countries/' . $this->geoData['country']->id, $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'name', 'iso2', 'iso3', 'phonecode', 'capital', 'currency',
                    'region', 'sub_region', 'timezones',
                ],
            ]);
    }

    /** @test */
    public function it_gets_country_states()
    {
        $response = $this->getJson('/api/v1/countries/' . $this->geoData['country']->id . '/states', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function it_gets_country_cities()
    {
        $response = $this->getJson('/api/v1/countries/' . $this->geoData['country']->id . '/cities', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_gets_country_timezones()
    {
        $response = $this->getJson('/api/v1/countries/' . $this->geoData['country']->id . '/timezones', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_gets_country_neighbors()
    {
        $this->createSecondCountry($this->geoData['region'], $this->geoData['subRegion']);

        $response = $this->getJson('/api/v1/countries/' . $this->geoData['country']->id . '/neighbors', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_compares_two_countries()
    {
        $country2 = $this->createSecondCountry($this->geoData['region'], $this->geoData['subRegion']);

        $response = $this->getJson('/api/v1/countries/compare?c1_id=' . $this->geoData['country']->id . '&c2_id=' . $country2->id, $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'countries',
                    'comparison',
                ],
            ]);
    }

    /** @test */
    public function it_returns_error_when_comparing_missing_countries()
    {
        $response = $this->getJson('/api/v1/countries/compare', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    // ─── STATES ────────────────────────────────────────────────────

    /** @test */
    public function it_fetches_states()
    {
        $response = $this->getJson('/api/v1/states', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_filters_states_by_country_id()
    {
        $response = $this->getJson('/api/v1/states?country_id=' . $this->geoData['country']->id, $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function it_gets_state_detail()
    {
        $response = $this->getJson('/api/v1/states/' . $this->geoData['state']->id, $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'state_code', 'country'],
            ]);
    }

    /** @test */
    public function it_gets_state_cities()
    {
        $response = $this->getJson('/api/v1/states/' . $this->geoData['state']->id . '/cities', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // ─── CITIES ────────────────────────────────────────────────────

    /** @test */
    public function it_fetches_cities()
    {
        $response = $this->getJson('/api/v1/cities', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_filters_cities_by_state_id()
    {
        $response = $this->getJson('/api/v1/cities?state_id=' . $this->geoData['state']->id, $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    /** @test */
    public function it_gets_city_detail()
    {
        $response = $this->getJson('/api/v1/cities/' . $this->geoData['city']->id, $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'state', 'country'],
            ]);
    }

    // ─── PINCODES ──────────────────────────────────────────────────

    /** @test */
    public function it_fetches_pincodes()
    {
        $response = $this->getJson('/api/v1/pincodes', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_searches_pincode()
    {
        $response = $this->getJson('/api/v1/pincodes/search?pincode=400001', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('400001', $data[0]['pincode']);
    }

    /** @test */
    public function it_returns_error_for_missing_pincode_param()
    {
        $response = $this->getJson('/api/v1/pincodes/search', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_pincode()
    {
        $response = $this->getJson('/api/v1/pincodes/search?pincode=999999', $this->headers);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    // ─── CURRENCY ──────────────────────────────────────────────────

    /** @test */
    public function it_gets_currency_exchange_rate()
    {
        $this->createCurrencyData();

        $response = $this->getJson('/api/v1/currency/exchange?currency=EUR', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['base_currency', 'exchange_rates', 'last_updated'],
            ]);
    }

    /** @test */
    public function it_returns_error_for_missing_currency_param()
    {
        $response = $this->getJson('/api/v1/currency/exchange', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_converts_currency()
    {
        $this->createCurrencyData();

        $response = $this->getJson('/api/v1/currency/convert?from=EUR&to=GBP&amount=100', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['from', 'to', 'amount', 'converted_amount', 'exchange_rate'],
            ]);
    }

    /** @test */
    public function it_returns_error_for_missing_conversion_params()
    {
        $response = $this->getJson('/api/v1/currency/convert', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_returns_error_for_zero_amount()
    {
        $response = $this->getJson('/api/v1/currency/convert?from=EUR&to=GBP&amount=0', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    // ─── ADDRESS VALIDATION ────────────────────────────────────────

    /** @test */
    public function it_validates_address_with_valid_pincode()
    {
        $response = $this->getJson('/api/v1/address/validate?pincode=400001', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['is_valid', 'pincode', 'matches'],
            ]);
    }

    /** @test */
    public function it_validates_address_cross_checks_state_and_city()
    {
        $response = $this->getJson('/api/v1/address/validate?pincode=400001&state=Maharashtra&city=Mumbai', $this->headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_valid' => true],
            ]);
    }

    /** @test */
    public function it_warns_on_mismatched_state()
    {
        $response = $this->getJson('/api/v1/address/validate?pincode=400001&state=Karnataka', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertFalse($data['is_valid']);
        $this->assertNotEmpty($data['warnings']);
    }

    // ─── ADDRESS AUTOCOMPLETE ──────────────────────────────────────

    /** @test */
    public function it_returns_autocomplete_suggestions()
    {
        $response = $this->getJson('/api/v1/address/autocomplete?q=Mum', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_returns_empty_for_short_query()
    {
        $response = $this->getJson('/api/v1/address/autocomplete?q=Mu', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    // ─── TIMEZONE CONVERSION ───────────────────────────────────────

    /** @test */
    public function it_converts_timezones()
    {
        $response = $this->getJson('/api/v1/timezones/convert?from=Asia/Kolkata&to=America/New_York&time=12:00:00', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['from' => ['zone', 'time'], 'to' => ['zone', 'time']],
            ]);
    }

    /** @test */
    public function it_returns_error_for_missing_timezone_params()
    {
        $response = $this->getJson('/api/v1/timezones/convert', $this->headers);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    // ─── USER USAGE ────────────────────────────────────────────────

    /** @test */
    public function it_returns_user_usage()
    {
        $response = $this->getJson('/api/v1/user/usage', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'plan_name', 'total_credits', 'used_credits', 'available_credits', 'expires_at',
                ],
            ]);
    }

    // ─── PAGINATION ────────────────────────────────────────────────

    /** @test */
    public function it_respects_limit_parameter()
    {
        $response = $this->getJson('/api/v1/countries?limit=1', $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(1, count($data));
    }

    // ─── FALLBACK ──────────────────────────────────────────────────

    /** @test */
    public function it_returns_404_for_unknown_api_endpoint()
    {
        $response = $this->getJson('/api/v1/nonexistent-endpoint', $this->headers);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
