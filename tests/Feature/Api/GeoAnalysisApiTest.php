<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeoAnalysisApiTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $geoData;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->authData = $this->createAuthenticatedApiUser();
        $this->geoData = $this->createGeoHierarchy();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    // ─── STATISTICS (Free - no credits) ────────────────────────────

    /** @test */
    public function it_fetches_global_statistics()
    {
        $response = $this->getJson('/api/v1/geospatial/statistics', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['total_countries', 'total_states', 'total_cities', 'total_pincodes'],
            ]);
    }

    /** @test */
    public function it_fetches_statistics_filtered_by_country()
    {
        $response = $this->getJson('/api/v1/geospatial/statistics?country_id=' . $this->geoData['country']->id, $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_fetches_statistics_filtered_by_state()
    {
        $response = $this->getJson('/api/v1/geospatial/statistics?country_id=' . $this->geoData['country']->id . '&state_id=' . $this->geoData['state']->id, $this->headers);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('state_cities', $data);
    }

    // ─── DISTANCE ──────────────────────────────────────────────────

    /** @test */
    public function it_calculates_distance_in_km()
    {
        $response = $this->getJson('/api/v1/geospatial/distance?lat1=19.076&lng1=72.877&lat2=28.613&lng2=77.209', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['distance', 'unit', 'unit_label', 'distance_km'],
            ]);

        $distance = $response->json('data.distance');
        // Mumbai to Delhi is roughly 1150-1200 km
        $this->assertGreaterThan(1000, $distance);
        $this->assertLessThan(1400, $distance);
    }

    /** @test */
    public function it_calculates_distance_in_miles()
    {
        $response = $this->getJson('/api/v1/geospatial/distance?lat1=19.076&lng1=72.877&lat2=28.613&lng2=77.209&unit=miles', $this->headers);

        $response->assertStatus(200);
        $this->assertEquals('miles', $response->json('data.unit'));
    }

    /** @test */
    public function it_validates_distance_parameters()
    {
        $response = $this->getJson('/api/v1/geospatial/distance?lat1=999&lng1=72.877&lat2=28.613&lng2=77.209', $this->headers);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_missing_distance_params()
    {
        $response = $this->getJson('/api/v1/geospatial/distance', $this->headers);

        $response->assertStatus(422);
    }

    // ─── NEARBY ────────────────────────────────────────────────────

    /** @test */
    public function it_finds_nearby_cities()
    {
        $response = $this->getJson('/api/v1/geospatial/nearby?lat=19.076&lng=72.877&radius=50&type=city', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_finds_nearby_pincodes()
    {
        $response = $this->getJson('/api/v1/geospatial/nearby?lat=18.94&lng=72.83&radius=10&type=pincode', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_validates_nearby_parameters()
    {
        $response = $this->getJson('/api/v1/geospatial/nearby?lat=19.076&lng=72.877&type=invalid', $this->headers);

        $response->assertStatus(422);
    }

    // ─── GEOCODE ───────────────────────────────────────────────────

    /** @test */
    public function it_reverse_geocodes_coordinates()
    {
        $response = $this->getJson('/api/v1/geospatial/geocode?lat=19.076&lng=72.877', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['city', 'state', 'country', 'formatted_address'],
            ]);
    }

    /** @test */
    public function it_returns_404_for_remote_coordinates()
    {
        // Middle of the Pacific Ocean
        $response = $this->getJson('/api/v1/geospatial/geocode?lat=0&lng=-160', $this->headers);

        $response->assertStatus(404);
    }

    // ─── BOUNDARY ──────────────────────────────────────────────────

    /** @test */
    public function it_retrieves_locations_in_boundary()
    {
        $response = $this->getJson('/api/v1/geospatial/boundary?min_lat=18&max_lat=20&min_lng=72&max_lng=74&type=city', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_validates_boundary_parameters()
    {
        $response = $this->getJson('/api/v1/geospatial/boundary?min_lat=18&type=city', $this->headers);

        $response->assertStatus(422);
    }

    // ─── CLUSTER ───────────────────────────────────────────────────

    /** @test */
    public function it_clusters_locations()
    {
        $response = $this->getJson('/api/v1/geospatial/cluster?lat=19.076&lng=72.877&radius=100', $this->headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
