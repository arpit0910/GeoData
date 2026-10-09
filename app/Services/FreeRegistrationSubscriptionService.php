<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionFeature;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FreeRegistrationSubscriptionService
{
    public function __construct(private readonly SubscriptionAssignmentService $assignments)
    {
    }

    public function provision(User $user): Subscription
    {
        return DB::transaction(function () use ($user) {
            // This is an internal onboarding tier, not one of the public/admin
            // subscription products seeded by PlanSeeder.
            $plan = Plan::updateOrCreate(
                ['name' => 'Free Developer', 'billing_cycle' => 'monthly'],
                [
                    'amount' => 0,
                    'discount_amount' => 0,
                    'api_hits_limit' => 1000,
                    'status' => 0,
                    'terms' => 'Free access to IFSC and Indian pincode APIs.',
                    'benefits' => ['IFSC lookup', 'India pincode lookup', '1,000 API calls per month'],
                ]
            );

            $featureIds = collect([
                [SubscriptionFeature::MODULE_IFSC_API, 'IFSC API', 'Single bank branch lookup by IFSC code.'],
                [SubscriptionFeature::MODULE_INDIA_PINCODE_API, 'India Pincode API', 'Single-object Indian pincode detail lookup.'],
            ])->map(fn (array $feature) => SubscriptionFeature::firstOrCreate(
                ['key' => $feature[0]],
                ['name' => $feature[1], 'description' => $feature[2], 'is_active' => true]
            )->id);

            $plan->features()->syncWithoutDetaching($featureIds);

            $existing = $user->subscriptions()
                ->where('plan_id', $plan->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }

            $subscription = $this->assignments->assign($user, $plan, [
                'razorpay_order_id' => 'free-registration-' . $user->id . '-' . Str::lower(Str::random(12)),
            ]);

            return $subscription;
        });
    }
}
