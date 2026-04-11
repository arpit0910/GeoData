<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TransactionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class RazorpayWebhookTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected $user;
    protected $paidPlan;
    protected $freePlan;
    protected $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->freePlan = $this->createPlan([
            'name' => 'Bronze',
            'amount' => 0,
            'api_hits_limit' => 5000,
            'billing_cycle' => 'monthly'
        ]);

        $this->paidPlan = $this->createPlan([
            'name' => 'Silver',
            'amount' => 299,
            'api_hits_limit' => 250000,
            'billing_cycle' => 'monthly'
        ]);

        $this->user = $this->createUser();
        $this->subscription = $this->createActiveSubscription($this->user, $this->paidPlan, [
            'razorpay_subscription_id' => 'sub_test_123',
            'available_credits' => 100 // Almost exhausted
        ]);
        $this->user->update(['plan_id' => $this->paidPlan->id]);
    }

    /** @test */
    public function it_handles_subscription_charged_webhook_success()
    {
        $newEnd = now()->addMonth()->getTimestamp();
        
        $payload = [
            'event' => 'subscription.charged',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => 'sub_test_123',
                        'current_end' => $newEnd,
                    ]
                ],
                'payment' => [
                    'entity' => [
                        'id' => 'pay_test_renewal',
                        'amount' => 29900,
                        'order_id' => 'order_test_renewal'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload);

        $response->assertStatus(200);

        $this->subscription->refresh();
        $this->user->refresh();

        // Verify credits reset
        $this->assertEquals(250000, $this->subscription->available_credits);
        $this->assertEquals(250000, $this->user->available_credits);
        
        // Verify expiration updated
        $this->assertEquals($newEnd, $this->subscription->expires_at->getTimestamp());

        // Verify transaction recorded
        $this->assertDatabaseHas('transaction_histories', [
            'user_id' => $this->user->id,
            'type' => 'renewal',
            'razorpay_payment_id' => 'pay_test_renewal'
        ]);
    }

    /** @test */
    public function it_handles_subscription_cancelled_webhook_downgrade()
    {
        $payload = [
            'event' => 'subscription.cancelled',
            'payload' => [
                'subscription' => [
                    'entity' => [
                        'id' => 'sub_test_123',
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/webhooks/razorpay', $payload);

        $response->assertStatus(200);

        $this->user->refresh();

        // Verify user plan changed to Free (Bronze)
        $this->assertEquals($this->freePlan->id, $this->user->plan_id);
        $this->assertEquals(5000, $this->user->available_credits);

        // Verify old subscription is expired
        $this->subscription->refresh();
        $this->assertEquals('expired', $this->subscription->status);

        // Verify NEW free subscription created
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->freePlan->id,
            'status' => 'active'
        ]);
    }
}
