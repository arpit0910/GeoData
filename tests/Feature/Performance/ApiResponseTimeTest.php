<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiResponseTimeTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $headers;

    /**
     * Maximum acceptable response time in milliseconds for each endpoint tier.
     */
    const FAST_THRESHOLD_MS   = 200;   // Simple lookups
    const MEDIUM_THRESHOLD_MS = 500;   // Filtered queries
    const SLOW_THRESHOLD_MS   = 1000;  // Complex computations / joins

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->authData = $this->createAuthenticatedApiUser();
        $this->createBankData();
        $this->createCurrencyData();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    /**
     * Helper: measure response time for an endpoint.
     */
    protected function measureResponseTime(string $method, string $url, array $data = []): array
    {
        $start = microtime(true);

        if ($method === 'GET') {
            $response = $this->getJson($url, $this->headers);
        } else {
            $response = $this->postJson($url, $data, $this->headers);
        }

        $elapsed = round((microtime(true) - $start) * 1000, 2); // ms

        return [
            'response' => $response,
            'time_ms' => $elapsed,
        ];
    }

    // ─── AUTH ENDPOINT ──────────────────────────────────────────────

    /** @test */
    public function token_generation_response_time()
    {
        $user = $this->createUser();
        $start = microtime(true);

        $response = $this->postJson('/api/v1/auth/token', [
            'client_key' => $user->client_key,
            'client_secret' => $user->client_secret,
        ]);

        $elapsed = round((microtime(true) - $start) * 1000, 2);

        $response->assertStatus(200);
        $this->assertLessThan(
            self::MEDIUM_THRESHOLD_MS,
            $elapsed,
            "Token generation: {$elapsed}ms (threshold: " . self::MEDIUM_THRESHOLD_MS . "ms)"
        );
    }

    // ─── LISTING ENDPOINTS ──────────────────────────────────────────

    /** @test */
    public function regions_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/regions');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Regions: {$result['time_ms']}ms");
    }

    /** @test */
    public function subregions_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/sub-regions');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "SubRegions: {$result['time_ms']}ms");
    }

    /** @test */
    public function timezones_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/timezones');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Timezones: {$result['time_ms']}ms");
    }

    /** @test */
    public function countries_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/countries');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Countries: {$result['time_ms']}ms");
    }

    /** @test */
    public function states_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/states');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "States: {$result['time_ms']}ms");
    }

    /** @test */
    public function cities_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/cities');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Cities: {$result['time_ms']}ms");
    }

    /** @test */
    public function pincodes_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/pincodes');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Pincodes: {$result['time_ms']}ms");
    }

    /** @test */
    public function banks_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/banks');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Banks: {$result['time_ms']}ms");
    }

    // ─── DETAIL ENDPOINTS ───────────────────────────────────────────

    /** @test */
    public function country_detail_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/countries/1');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Country Detail: {$result['time_ms']}ms");
    }

    /** @test */
    public function state_detail_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/states/1');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "State Detail: {$result['time_ms']}ms");
    }

    /** @test */
    public function city_detail_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/cities/1');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "City Detail: {$result['time_ms']}ms");
    }

    // ─── SEARCH/COMPLEX ENDPOINTS ────────────────────────────────────

    /** @test */
    public function pincode_search_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/pincodes/search?pincode=400001');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Pincode Search: {$result['time_ms']}ms");
    }

    /** @test */
    public function branch_search_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/branch/search?search_query=Fort');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Branch Search: {$result['time_ms']}ms");
    }

    /** @test */
    public function currency_exchange_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/currency/exchange?currency=EUR');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Currency Exchange: {$result['time_ms']}ms");
    }

    /** @test */
    public function currency_convert_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/currency/convert?from=EUR&to=GBP&amount=100');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Currency Convert: {$result['time_ms']}ms");
    }

    /** @test */
    public function address_validate_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/address/validate?pincode=400001');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Address Validate: {$result['time_ms']}ms");
    }

    /** @test */
    public function address_autocomplete_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/address/autocomplete?q=Mum');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Autocomplete: {$result['time_ms']}ms");
    }

    // ─── GEOSPATIAL ENDPOINTS ────────────────────────────────────────

    /** @test */
    public function distance_calculation_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/distance?lat1=19.076&lng1=72.877&lat2=28.613&lng2=77.209');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Distance: {$result['time_ms']}ms");
    }

    /** @test */
    public function nearby_search_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/nearby?lat=19.076&lng=72.877&radius=50&type=city');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::SLOW_THRESHOLD_MS, $result['time_ms'],
            "Nearby Search: {$result['time_ms']}ms");
    }

    /** @test */
    public function geocode_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/geocode?lat=19.076&lng=72.877');
        $this->assertLessThan(self::SLOW_THRESHOLD_MS, $result['time_ms'],
            "Geocode: {$result['time_ms']}ms");
    }

    /** @test */
    public function boundary_search_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/boundary?min_lat=18&max_lat=20&min_lng=72&max_lng=74&type=city');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "Boundary: {$result['time_ms']}ms");
    }

    /** @test */
    public function cluster_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/cluster?lat=19.076&lng=72.877&radius=100');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::SLOW_THRESHOLD_MS, $result['time_ms'],
            "Cluster: {$result['time_ms']}ms");
    }

    /** @test */
    public function statistics_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/geospatial/statistics');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $result['time_ms'],
            "Statistics: {$result['time_ms']}ms");
    }

    /** @test */
    public function user_usage_response_time()
    {
        $result = $this->measureResponseTime('GET', '/api/v1/user/usage');
        $result['response']->assertStatus(200);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $result['time_ms'],
            "User Usage: {$result['time_ms']}ms");
    }

    // ─── AGGREGATE RESPONSE TIME REPORT ──────────────────────────────

    /** @test */
    public function all_endpoints_average_under_threshold()
    {
        $endpoints = [
            '/api/v1/regions',
            '/api/v1/sub-regions',
            '/api/v1/timezones',
            '/api/v1/countries',
            '/api/v1/states',
            '/api/v1/cities',
            '/api/v1/pincodes',
            '/api/v1/banks',
        ];

        $totalTime = 0;
        $count = count($endpoints);

        foreach ($endpoints as $endpoint) {
            $result = $this->measureResponseTime('GET', $endpoint);
            $result['response']->assertStatus(200);
            $totalTime += $result['time_ms'];
        }

        $avgTime = round($totalTime / $count, 2);

        $this->assertLessThan(
            self::MEDIUM_THRESHOLD_MS,
            $avgTime,
            "Average response time across {$count} endpoints: {$avgTime}ms"
        );
    }
}
