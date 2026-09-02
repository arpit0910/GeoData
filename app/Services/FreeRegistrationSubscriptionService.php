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
    public function provision(User $user): Subscription
    {
        return DB::transaction(function () use ($user) {
            $plan = Plan::firstOrCreate(
                ['name' => 'Free Developer', 'billing_cycle' => 'monthly'],
                [
                    'amount' => 0,
                    'discount_amount' => 0,
                    'api_hits_limit' => 1000,
                    'status' => 1,
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

            $credits = $plan->api_hits_limit ?? 0;
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => 'free-registration-' . $user->id . '-' . Str::lower(Str::random(12)),
                'amount_paid' => 0,
                'discount_amount' => 0,
                'remaining_discount_cycles' => 0,
                'status' => 'active',
                'expires_at' => now()->addMonth(),
                'total_credits' => $credits,
                'used_credits' => 0,
                'available_credits' => $credits,
                'last_credit_refresh' => now(),
            ]);

            $user->forceFill([
                'plan_id' => $plan->id,
                'available_credits' => $credits,
            ])->save();

            return $subscription;
        });
    }
}
