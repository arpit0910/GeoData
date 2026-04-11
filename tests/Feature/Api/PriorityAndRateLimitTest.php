<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

class PriorityAndRateLimitTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
    }

    /** @test */
    public function free_user_has_normal_priority_and_delay_headers_when_paid_users_active()
    {
        $freeData = $this->createFreeApiUser();
        $this->createGeoHierarchy();

        // Simulate active paid requests to trigger traffic shaping
        \Illuminate\Support\Facades\Cache::put('api:paid_requests_active', 1);

        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer ' . $freeData['token'],
            'X-Test-Priority' => 'true'
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-Api-Priority', 'Normal')
            ->assertHeader('X-Api-Tier', 'Free')
            ->assertHeader('X-Response-Time-Priority', 'Scheduled-Delay');
    }

    /** @test */
    public function free_user_has_immediate_response_when_system_is_idle()
    {
        $freeData = $this->createFreeApiUser();
        $this->createGeoHierarchy();

        // System is idle (counter is 0)
        \Illuminate\Support\Facades\Cache::put('api:paid_requests_active', 0);

        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer ' . $freeData['token']
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-Api-Priority', 'Normal')
            ->assertHeader('X-Api-Tier', 'Free')
            ->assertHeader('X-Response-Time-Priority', 'Immediate');
    }

    /** @test */
    public function paid_user_has_high_priority_and_immediate_headers()
    {
        $paidData = $this->createAuthenticatedApiUser();
        $this->createGeoHierarchy();

        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer ' . $paidData['token']
        ]);

        $response->assertStatus(200)
            ->assertHeader('X-Api-Priority', 'High')
            ->assertHeader('X-Api-Tier', 'Paid')
            ->assertHeader('X-Response-Time-Priority', 'Immediate');
    }

    /** @test */
    public function free_user_rate_limit_is_respected()
    {
        $freeData = $this->createFreeApiUser();
        $headers = ['Authorization' => 'Bearer ' . $freeData['token']];
        $this->createGeoHierarchy();

        // Hit the limit (60 requests)
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/regions', $headers)->assertStatus(200);
        }

        // 61st request should fail
        $this->getJson('/api/v1/regions', $headers)->assertStatus(429);
    }

    /** @test */
    public function paid_user_rate_limit_is_respected()
    {
        $paidData = $this->createAuthenticatedApiUser();
        $headers = ['Authorization' => 'Bearer ' . $paidData['token']];
        $this->createGeoHierarchy();

        // Hit first 60 requests (which would block a free user)
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/regions', $headers)->assertStatus(200);
        }

        // Paid user should still be able to continue (limit is 300)
        $this->getJson('/api/v1/regions', $headers)->assertStatus(200);
    }
}
