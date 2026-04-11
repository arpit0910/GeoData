<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\TransactionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class SubscriptionController extends Controller
{
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $coupon = Coupon::where('code', strtoupper($request->code))->where('status', 1)->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'Invalid coupon code.'], 404);
        }

        if ($coupon->isExpired()) {
            return response()->json(['success' => false, 'message' => 'This coupon has expired.'], 400);
        }

        if (!$coupon->hasRedemptionsLeft()) {
            return response()->json(['success' => false, 'message' => 'Coupon redemption limit reached.'], 400);
        }

        if (!$coupon->isValidForPlan($request->plan_id)) {
            return response()->json(['success' => false, 'message' => 'This coupon is not valid for the selected plan.'], 400);
        }

        if ($coupon->single_use_per_user && $coupon->users()->where('user_id', Auth::id())->exists()) {
            return response()->json(['success' => false, 'message' => 'You have already used this coupon.'], 400);
        }

        $plan = Plan::find($request->plan_id);
        $originalAmount = $plan->amount - $plan->discount_amount;
        $discountAmount = 0;

        if ($coupon->discount_type === 'fixed') {
            $discountAmount = $coupon->discount_value;
        } else {
            $discountAmount = ($originalAmount * $coupon->discount_value) / 100;
            if ($coupon->max_discount && $discountAmount > $coupon->max_discount) {
                $discountAmount = $coupon->max_discount;
            }
        }

        $finalAmount = max(0, $originalAmount - $discountAmount);

        return response()->json([
            'success' => true,
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
            'message' => 'Coupon applied successfully!'
        ]);
    }

    public function pricing()
    {
        $plans = Plan::where('status', 1)->get();
        $activeSubscription = auth()->check() ? auth()->user()->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first() : null;
            
        return view('subscriptions.pricing', compact('plans', 'activeSubscription'));
    }

    public function createOrder(Request $request, Plan $plan)
    {
        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');

        $api = new Api($keyId, $keySecret);
        
        $amount = ($plan->amount - $plan->discount_amount);
        
        // Handle Coupon discount if provided
        if ($request->has('coupon_id')) {
            $coupon = Coupon::find($request->coupon_id);
            if ($coupon && $coupon->status && $coupon->isValidForPlan($plan->id)) {
                $discount = 0;
                if ($coupon->discount_type === 'fixed') {
                    $discount = $coupon->discount_value;
                } else {
                    $discount = ($amount * $coupon->discount_value) / 100;
                    if ($coupon->max_discount && $discount > $coupon->max_discount) {
                        $discount = $coupon->max_discount;
                    }
                }
                $amount = max(0, $amount - $discount);
            }
        }

        $amountPaise = $amount * 100;
        
        if ($amountPaise <= 0) {
            return response()->json([
                'order_id' => 'free_plan_' . time(),
                'amount' => 0,
                'key' => $keyId
            ]);
        }

        $orderData = [
            'receipt'         => 'rcpt_' . Auth::id() . '_' . time(),
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'payment_capture' => 1,
            'notes'           => [
                'plan_id' => $plan->id,
                'coupon_id' => $request->coupon_id ?? null,
            ]
        ];

        try {
            $razorpayOrder = $api->order->create($orderData);
            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $amountPaise,
                'key' => $keyId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verifyPayment(Request $request)
    {
        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');
        
        $plan = Plan::findOrFail($request->plan_id);
        $amountPaid = $plan->amount - $plan->discount_amount;
        $couponId = $request->coupon_id;
        $discountAmount = 0;
        $remainingCycles = 0;

        if ($couponId) {
            $coupon = Coupon::find($couponId);
            if ($coupon) {
                if ($coupon->discount_type === 'fixed') {
                    $discountAmount = $coupon->discount_value;
                } else {
                    $discountAmount = ($amountPaid * $coupon->discount_value) / 100;
                    if ($coupon->max_discount && $discountAmount > $coupon->max_discount) {
                        $discountAmount = $coupon->max_discount;
                    }
                }
                $amountPaid = max(0, $amountPaid - $discountAmount);
                $remainingCycles = $coupon->apply_to_cycles - 1;
            }
        }

        $orderId = $request->razorpay_order_id;
        $paymentId = $request->razorpay_payment_id;
        $signature = $request->razorpay_signature;

        if ($amountPaid > 0 && strpos($orderId, 'free_plan_') === false) {
            $api = new Api($keyId, $keySecret);
            try {
                $attributes = [
                    'razorpay_order_id' => $orderId,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_signature' => $signature
                ];
                $api->utility->verifyPaymentSignature($attributes);
            } catch (SignatureVerificationError $e) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Payment verification failed: ' . $e->getMessage()
                ], 400);
            }
        }

        try {
            $subscription = $this->activateSubscription(
                Auth::user(),
                $plan,
                $orderId,
                $paymentId,
                $signature,
                $amountPaid,
                $discountAmount,
                $remainingCycles,
                $couponId
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription activated successfully!',
                'subscription' => $subscription,
                'plan_details' => [
                    'name' => $plan->name,
                    'expires_at' => $subscription->expires_at ? $subscription->expires_at->format('d M, Y') : 'Never',
                    'benefits' => $plan->benefits,
                    'credits' => number_format($subscription->total_credits)
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Subscription Activation Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'plan_id' => $plan->id,
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Subscription activation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $webhookSecret = env('RAZORPAY_WEBHOOK_SECRET');
        $signature = $request->header('X-Razorpay-Signature');
        
        if ($webhookSecret && $signature) {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            try {
                $api->utility->verifyWebhookSignature($request->getContent(), $signature, $webhookSecret);
            } catch (SignatureVerificationError $e) {
                return response()->json(['success' => false, 'message' => 'Invalid webhook signature'], 400);
            }
        }

        $payload = $request->all();
        $event = $payload['event'];

        try {
            switch ($event) {
                case 'payment.captured':
                case 'order.paid':
                    $payment = $payload['payload']['payment']['entity'];
                    $orderId = $payment['order_id'];
                    
                    if (Subscription::where('razorpay_order_id', $orderId)->where('status', 'active')->exists()) {
                        return response()->json(['success' => true]);
                    }

                    $user = User::where('email', $payment['email'])->first();
                    if ($user && isset($payment['notes']['plan_id'])) {
                        $plan = Plan::find($payment['notes']['plan_id']);
                        if ($plan) {
                            $this->activateSubscription(
                                $user,
                                $plan,
                                $orderId,
                                $payment['id'],
                                'webhook',
                                $payment['amount'] / 100,
                                0, 0
                            );
                        }
                    }
                    break;

                case 'subscription.charged':
                    $subscriptionPayload = $payload['payload']['subscription']['entity'];
                    $paymentPayload = $payload['payload']['payment']['entity'];
                    
                    $subscription = Subscription::where('razorpay_subscription_id', $subscriptionPayload['id'])
                        ->orWhere('razorpay_order_id', $paymentPayload['order_id'])
                        ->first();
                    
                    if ($subscription) {
                        $this->renewSubscription($subscription, $subscriptionPayload, $paymentPayload);
                    }
                    break;

                case 'subscription.cancelled':
                case 'subscription.expired':
                case 'subscription.halted':
                    $subscriptionPayload = $payload['payload']['subscription']['entity'];
                    $subscription = Subscription::where('razorpay_subscription_id', $subscriptionPayload['id'])->first();
                    
                    if ($subscription) {
                        $this->downgradeToFree($subscription->user);
                    }
                    break;
            }
        } catch (\Exception $e) {
            \Log::error('Razorpay Webhook Processing Failed: ' . $e->getMessage(), [
                'event' => $event,
                'payload' => $payload,
                'exception' => $e
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    }

    private function renewSubscription($subscription, $subscriptionPayload, $paymentPayload)
    {
        DB::transaction(function() use ($subscription, $subscriptionPayload, $paymentPayload) {
            $plan = $subscription->plan;
            $user = $subscription->user;
            
            // Update expiration to the end of the new period
            $expiresAt = \Carbon\Carbon::createFromTimestamp($subscriptionPayload['current_end']);
            
            $creditsToAdd = $plan->api_hits_limit ?? 999999999;

            $subscription->update([
                'status' => 'active',
                'expires_at' => $expiresAt,
                'available_credits' => $creditsToAdd,
                'total_credits' => $creditsToAdd,
                'used_credits' => 0,
                'razorpay_subscription_id' => $subscriptionPayload['id'],
            ]);

            TransactionHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'razorpay_payment_id' => $paymentPayload['id'],
                'razorpay_order_id' => $paymentPayload['order_id'] ?? 'renewal_' . time(),
                'amount' => $paymentPayload['amount'] / 100,
                'plan_name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'status' => 'success',
                'type' => 'renewal',
                'credits' => $creditsToAdd,
            ]);

            $user->update(['available_credits' => $creditsToAdd]);
        });
    }

    private function downgradeToFree($user)
    {
        $freePlan = Plan::where('amount', 0)->first();
        if (!$freePlan) {
            return;
        }

        DB::transaction(function() use ($user, $freePlan) {
            // Expire any active subscriptions
            Subscription::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'expired']);

            // Create a pseudo-subscription for the free tier
            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $freePlan->id,
                'razorpay_order_id' => 'free_' . uniqid() . '_' . time(),
                'status' => 'active',
                'expires_at' => now()->addYears(10),
                'total_credits' => $freePlan->api_hits_limit,
                'used_credits' => 0,
                'available_credits' => $freePlan->api_hits_limit,
                'amount_paid' => 0,
            ]);

            $user->update([
                'plan_id' => $freePlan->id,
                'available_credits' => $freePlan->api_hits_limit,
            ]);
        });
    }

    private function activateSubscription($user, $plan, $orderId, $paymentId, $signature, $amountPaid, $discountAmount, $remainingCycles, $couponId = null)
    {
        return DB::transaction(function() use ($user, $plan, $orderId, $paymentId, $signature, $amountPaid, $discountAmount, $remainingCycles, $couponId) {
            // Calculate expiration date - Same date of next month/year
            $expiresAt = now();
            if ($plan->billing_cycle === 'monthly') {
                $expiresAt = $expiresAt->addMonth();
            } elseif ($plan->billing_cycle === 'yearly') {
                $expiresAt = $expiresAt->addYear();
            } else {
                // lifetime / free plans — effectively never expire
                $expiresAt = $expiresAt->addYears(100);
            }

            $creditsToAdd = $plan->api_hits_limit ?? 999999999;

            // Deactivate old subscriptions for this user
            Subscription::where('user_id', $user->id)->where('status', 'active')->update(['status' => 'expired']);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'coupon_id' => $couponId,
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'amount_paid' => $amountPaid,
                'discount_amount' => $discountAmount,
                'remaining_discount_cycles' => $remainingCycles,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'total_credits' => $creditsToAdd,
                'used_credits' => 0,
                'available_credits' => $creditsToAdd,
            ]);

            $coupon = $couponId ? Coupon::find($couponId) : null;
            $couponCode = $coupon ? $coupon->code : null;

            if ($coupon) {
                $coupon->increment('used_count');
                $coupon->users()->attach($user->id, ['subscription_id' => $subscription->id]);
            }

            // Record Transaction History
            TransactionHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'coupon_id' => $couponId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'amount' => $amountPaid,
                'discount_amount' => $discountAmount,
                'coupon_code' => $couponCode,
                'plan_name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'status' => 'success',
            ]);

            // Update User records
            $user->plan_id = $plan->id;
            $user->available_credits = $creditsToAdd; // Reset/Set to plan limit
            $user->status = 1; // User status is boolean (1=active)
            $user->save();

            return $subscription;
        });
    }

    public function transactions(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = TransactionHistory::where('user_id', Auth::id())
                ->with(['plan', 'coupon']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('plan_name', 'like', "%{$search}%")
                      ->orWhere('coupon_code', 'like', "%{$search}%")
                      ->orWhere('razorpay_payment_id', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 15;
            $start = $request->start ?? 0;
            
            $transactions = $query->latest()->skip($start)->take($limit)->get();

            $data = $transactions->map(function($transaction) {
                return array_merge($transaction->toArray(), [
                    'formatted_date' => Auth::user()->formatDate($transaction->created_at)
                ]);
            });

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => TransactionHistory::where('user_id', Auth::id())->count(),
                'recordsFiltered' => $total,
                'data' => $data
            ]);
        }

        $transactions = TransactionHistory::where('user_id', Auth::id())
            ->with(['plan', 'coupon'])
            ->latest()
            ->paginate(15);

        return view('subscriptions.transactions', compact('transactions'));
    }

    public function downloadReceipt($id)
    {
        $transaction = TransactionHistory::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['plan', 'coupon'])
            ->firstOrFail();

        $user = Auth::user();
        
        return view('subscriptions.receipt', compact('transaction', 'user'));
    }
}
