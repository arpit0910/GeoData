<?php

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\TransactionHistory;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlanSeeder::class);
    }

    public function test_plan_seeder_creates_the_four_products_in_monthly_and_yearly_cycles(): void
    {
        $this->assertDatabaseCount('plans', 8);

        foreach (['Address only', 'MF and Stocks', 'Banks', 'All in one'] as $name) {
            $this->assertDatabaseHas('plans', [
                'name' => $name,
                'billing_cycle' => 'monthly',
                'status' => 1,
            ]);
            $this->assertDatabaseHas('plans', [
                'name' => $name,
                'billing_cycle' => 'yearly',
                'status' => 1,
            ]);
        }

        $allInOne = Plan::query()
            ->where('name', 'All in one')
            ->where('billing_cycle', 'monthly')
            ->firstOrFail();

        $this->assertNull($allInOne->api_hits_limit);
        $this->assertTrue($allInOne->hasFeature(SubscriptionFeature::MODULE_ALL_API));
    }

    public function test_admin_plan_assignment_updates_the_existing_subscription_and_credits(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['available_credits' => 15]);
        $originalPlan = Plan::query()->where('name', 'Address only')->where('billing_cycle', 'monthly')->firstOrFail();
        $targetPlan = Plan::query()->where('name', 'MF and Stocks')->where('billing_cycle', 'yearly')->firstOrFail();
        $subscription = $this->subscriptionFor($customer, $originalPlan, 15);

        $this->actingAs($admin)
            ->postJson(route('admin.subscriptions.assign-plan', $subscription), [
                'plan_id' => $targetPlan->id,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $subscription->refresh();
        $customer->refresh();

        $this->assertSame(1, Subscription::query()->where('user_id', $customer->id)->count());
        $this->assertSame($targetPlan->id, $subscription->plan_id);
        $this->assertSame(100000, $subscription->total_credits);
        $this->assertSame(0, $subscription->used_credits);
        $this->assertSame(100000, $subscription->available_credits);
        $this->assertSame($targetPlan->id, $customer->plan_id);
        $this->assertSame(100000, $customer->available_credits);
        $this->assertTrue($subscription->expires_at->isBetween(now()->addYear()->subMinute(), now()->addYear()->addMinute()));
        $this->assertDatabaseHas('transaction_histories', [
            'subscription_id' => $subscription->id,
            'type' => 'admin_assignment',
            'credits' => 100000,
        ]);
    }

    public function test_admin_credit_assignment_updates_both_balances(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['available_credits' => 50000]);
        $plan = Plan::query()->where('name', 'Address only')->where('billing_cycle', 'monthly')->firstOrFail();
        $subscription = $this->subscriptionFor($customer, $plan, 50000);

        $this->actingAs($admin)
            ->postJson(route('admin.subscriptions.assign-credits', $subscription), ['credits' => 250])
            ->assertOk()
            ->assertJson(['status' => true]);

        $subscription->refresh();
        $customer->refresh();

        $this->assertSame(50250, $subscription->total_credits);
        $this->assertSame(50250, $subscription->available_credits);
        $this->assertSame(50000, $subscription->total_credits - 250);
        $this->assertSame(50250, $customer->available_credits);
        $this->assertSame(1, TransactionHistory::query()->where('type', 'credit')->count());
    }

    public function test_unlimited_plan_uses_one_subscription_and_rejects_credit_topups(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        $originalPlan = Plan::query()->where('name', 'Banks')->where('billing_cycle', 'monthly')->firstOrFail();
        $unlimitedPlan = Plan::query()->where('name', 'All in one')->where('billing_cycle', 'monthly')->firstOrFail();
        $subscription = $this->subscriptionFor($customer, $originalPlan, 75000);

        $this->actingAs($admin)
            ->postJson(route('admin.subscriptions.assign-plan', $subscription), ['plan_id' => $unlimitedPlan->id])
            ->assertOk();

        $subscription->refresh();
        $customer->refresh();

        $this->assertNull($subscription->total_credits);
        $this->assertNull($subscription->used_credits);
        $this->assertNull($subscription->available_credits);
        $this->assertSame(999999999, $customer->available_credits);
        $this->assertSame(1, Subscription::query()->where('user_id', $customer->id)->count());

        $this->postJson(route('admin.subscriptions.assign-credits', $subscription), ['credits' => 100])
            ->assertStatus(422)
            ->assertJson(['status' => false]);
    }

    public function test_database_prevents_a_second_subscription_record_for_the_same_customer(): void
    {
        $customer = User::factory()->create();
        $plan = Plan::query()->where('name', 'Address only')->where('billing_cycle', 'monthly')->firstOrFail();
        $this->subscriptionFor($customer, $plan, 50000);

        $this->expectException(QueryException::class);

        Subscription::query()->create([
            'user_id' => $customer->id,
            'plan_id' => $plan->id,
            'razorpay_order_id' => 'duplicate-'.Str::lower(Str::random(16)),
            'amount_paid' => 0,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'total_credits' => 50000,
            'used_credits' => 0,
            'available_credits' => 50000,
        ]);
    }

    private function subscriptionFor(User $user, Plan $plan, int $credits): Subscription
    {
        $user->forceFill(['plan_id' => $plan->id, 'available_credits' => $credits])->save();

        return Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'razorpay_order_id' => 'test-'.Str::lower(Str::random(16)),
            'amount_paid' => $plan->amount,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
            'total_credits' => $credits,
            'used_credits' => 0,
            'available_credits' => $credits,
        ]);
    }
}
