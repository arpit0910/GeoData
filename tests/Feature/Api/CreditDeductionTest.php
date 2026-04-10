<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ApiLog;

class CreditDeductionTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $authData;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authData = $this->createAuthenticatedApiUser();
        $this->createGeoHierarchy();
        $this->headers = ['Authorization' => 'Bearer ' . $this->authData['token']];
    }

    /** @test */
    public function it_deducts_credit_on_successful_api_call()
    {
        $initialCredits = $this->authData['subscription']->available_credits;

        $this->getJson('/api/v1/regions', $this->headers);

        $this->authData['subscription']->refresh();
        $this->assertEquals($initialCredits - 1, $this->authData['subscription']->available_credits);
        $this->assertEquals(1, $this->authData['subscription']->used_credits);
    }

    /** @test */
    public function it_logs_api_call()
    {
        $this->getJson('/api/v1/regions', $this->headers);

        $this->assertDatabaseHas('api_logs', [
            'user_id' => $this->authData['user']->id,
            'method' => 'GET',
            'status_code' => 200,
            'credit_deducted' => true,
        ]);
    }

    /** @test */
    public function it_returns_402_when_credits_exhausted()
    {
        // Exhaust all credits
        $this->authData['subscription']->update(['available_credits' => 0]);

        $response = $this->getJson('/api/v1/regions', $this->headers);

        $response->assertStatus(402)
            ->assertJson(['status' => false]);
    }

    /** @test */
    public function it_returns_402_when_subscription_expired()
    {
        $this->authData['subscription']->update(['expires_at' => now()->subDay()]);

        $response = $this->getJson('/api/v1/regions', $this->headers);

        $response->assertStatus(402);
    }

    /** @test */
    public function it_does_not_deduct_credit_on_failed_call()
    {
        $initialCredits = $this->authData['subscription']->available_credits;

        // This should return 400 (missing pincode param), not deduct credit
        $this->getJson('/api/v1/pincodes/search', $this->headers);

        $this->authData['subscription']->refresh();
        // Credit should NOT be deducted for 400-level responses
        $this->assertEquals($initialCredits, $this->authData['subscription']->available_credits);
    }

    /** @test */
    public function statistics_endpoint_does_not_deduct_credits()
    {
        $initialCredits = $this->authData['subscription']->available_credits;

        // Statistics is a free endpoint (auth:sanctum only, no api.credits middleware)
        $this->getJson('/api/v1/geospatial/statistics', $this->headers);

        $this->authData['subscription']->refresh();
        $this->assertEquals($initialCredits, $this->authData['subscription']->available_credits);
    }

    /** @test */
    public function it_tracks_multiple_api_calls_correctly()
    {
        $this->getJson('/api/v1/regions', $this->headers);
        $this->getJson('/api/v1/countries', $this->headers);
        $this->getJson('/api/v1/states', $this->headers);

        $this->authData['subscription']->refresh();
        $this->assertEquals(3, $this->authData['subscription']->used_credits);
        $this->assertEquals(
            $this->authData['subscription']->total_credits - 3,
            $this->authData['subscription']->available_credits
        );

        $this->assertEquals(3, ApiLog::where('user_id', $this->authData['user']->id)->count());
    }
}
