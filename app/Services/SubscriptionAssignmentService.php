<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionAssignmentService
{
    /**
     * Create or replace the customer's single subscription record.
     *
     * @param array<string, mixed> $attributes
     */
    public function assign(User $user, Plan $plan, array $attributes = []): Subscription
    {
        return DB::transaction(function () use ($user, $plan, $attributes): Subscription {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $credits = $plan->api_hits_limit;
            $userCredits = $credits ?? 999999999;
            $expiresAt = match ($plan->billing_cycle) {
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                default => now()->addYears(100),
            };

            $subscription = Subscription::query()->firstOrNew(['user_id' => $lockedUser->id]);
            $subscription->fill(array_replace([
                'plan_id' => $plan->id,
                'razorpay_order_id' => 'subscription-'.$lockedUser->id.'-'.Str::lower(Str::random(12)),
                'razorpay_payment_id' => null,
                'razorpay_signature' => null,
                'razorpay_subscription_id' => null,
                'coupon_id' => null,
                'amount_paid' => 0,
                'discount_amount' => 0,
                'remaining_discount_cycles' => 0,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'total_credits' => $credits,
                'used_credits' => $credits === null ? null : 0,
                'available_credits' => $credits,
                'last_credit_refresh' => now(),
            ], $attributes));
            $subscription->save();

            $lockedUser->forceFill([
                'plan_id' => $plan->id,
                'available_credits' => $userCredits,
                'status' => 1,
            ])->save();

            return $subscription->fresh(['user', 'plan']);
        });
    }
}
