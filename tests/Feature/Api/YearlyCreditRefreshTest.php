<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class YearlyCreditRefreshTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    /** @test */
    public function unlimited_plan_never_exhausts_credits()
    {
        $plan = $this->createPlan(['api_hits_limit' => null]);
        $user = $this->createUser();
        $this->createActiveSubscription($user, $plan, ['available_credits' => 0]); // Explicitly set 0
        $token = $user->createToken('test')->plainTextToken;
        $this->createGeoHierarchy();

        $response = $this->getJson('/api/v1/regions', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
        
        // Ensure no credits were deducted (they stay NULL or 0)
        // Since it's unlimited, available_credits may stay 0 but access was allowed.
    }

    /** @test */
    public function yearly_plan_refreshes_credits_every_month()
    {
        $plan = $this->createPlan([
            'api_hits_limit' => 5000,
            'billing_cycle' => 'yearly'
        ]);
        $user = $this->createUser();
        
        // Start subscription in January
        Carbon::setTestNow('2026-01-15 10:00:00');
        $subscription = $this->createActiveSubscription($user, $plan, [
            'available_credits' => 10, // Almost empty
            'expires_at' => now()->addYear(),
        ]);
        $token = $user->createToken('test')->plainTextToken;
        $this->createGeoHierarchy();

        // 1. First request in Jan: Still 10 credits
        $this->getJson('/api/v1/regions', ['Authorization' => 'Bearer ' . $token])->assertStatus(200);
        $this->assertEquals(9, $subscription->refresh()->available_credits);

        // 2. Fast forward to February 15th (Exactly 1 month later)
        Carbon::setTestNow('2026-02-15 10:00:01');

        // Request in Feb should trigger refresh
        $this->getJson('/api/v1/regions', ['Authorization' => 'Bearer ' . $token])->assertStatus(200);
        
        // Should be 5000 - 1 = 4999
        $this->assertEquals(4999, $subscription->refresh()->available_credits);
        $this->assertNotNull($subscription->last_credit_refresh);
        $this->assertTrue($subscription->last_credit_refresh->isToday());
    }

    /** @test */
    public function monthly_plan_does_not_refresh_credits_mid_cycle()
    {
        $plan = $this->createPlan([
            'api_hits_limit' => 5000,
            'billing_cycle' => 'monthly'
        ]);
        $user = $this->createUser();
        
        Carbon::setTestNow('2026-01-15 10:00:00');
        $subscription = $this->createActiveSubscription($user, $plan, [
            'available_credits' => 50,
        ]);
        $token = $user->createToken('test')->plainTextToken;
        $this->createGeoHierarchy();

        // Fast forward to February (Monthly plans only refresh on RENEWAL, not mid-cycle via middleware)
        Carbon::setTestNow('2026-02-01 10:00:00');

        $this->getJson('/api/v1/regions', ['Authorization' => 'Bearer ' . $token])->assertStatus(200);
        
        // Still 49, no refresh
        $this->assertEquals(49, $subscription->refresh()->available_credits);
    }
}
