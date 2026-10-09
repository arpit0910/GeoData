<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Coupon;
use App\Models\TransactionHistory;
use App\Services\SubscriptionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionAssignmentService $assignments)
    {
    }

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
        $plans = Plan::query()
            ->where('status', 1)
            ->with('benefitItems')
            ->orderByRaw("CASE billing_cycle WHEN 'monthly' THEN 0 WHEN 'yearly' THEN 1 ELSE 2 END")
            ->orderBy('amount')
            ->get();
        $monthlyPlans = $plans->where('billing_cycle', 'monthly')->values();
        $yearlyPlans = $plans->where('billing_cycle', 'yearly')->values();
        $activeSubscription = auth()->check() ? auth()->user()->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first() : null;
            
        $temporaryCheckoutEnabled = (bool) config('services.subscriptions.temporary_checkout_enabled', true);
        $paymentCheckoutEnabled = (bool) config('services.subscriptions.purchases_enabled', false);

        return view('subscriptions.pricing', compact(
            'plans',
            'monthlyPlans',
            'yearlyPlans',
            'activeSubscription',
            'temporaryCheckoutEnabled',
            'paymentCheckoutEnabled'
        ));
    }

    public function purchaseWithoutGateway(Request $request, Plan $plan)
    {
        abort_unless((bool) $plan->status, 404);

        if (!(bool) config('services.subscriptions.temporary_checkout_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Temporary checkout is no longer available. Please use the configured payment option.',
            ], 503);
        }

        $user = $request->user();
        $currentSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($currentSubscription) {
            return response()->json([
                'success' => true,
                'message' => 'This plan is already active on your account.',
                'redirect_url' => route('dashboard'),
            ]);
        }

        try {
            $reference = 'temporary-'.$user->id.'-'.$plan->id.'-'.Str::lower(Str::random(12));
            $subscription = $this->activateSubscription(
                $user,
                $plan,
                $reference,
                null,
                null,
                0,
                0,
                0,
                null,
                'temporary_checkout'
            );

            return response()->json([
                'success' => true,
                'message' => 'Your subscription has been activated successfully.',
                'redirect_url' => route('dashboard'),
                'subscription' => [
                    'plan' => $plan->name,
                    'billing_cycle' => $plan->billing_cycle,
                    'expires_at' => optional($subscription->expires_at)->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $exception) {
            \Log::error('Temporary subscription activation failed.', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'exception' => $exception,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The subscription could not be activated. Please try again.',
            ], 500);
        }
    }

    public function createOrder(Request $request, Plan $plan)
    {
        abort_unless((bool) $plan->status, 404);

        $request->validate([
            'coupon_id' => 'nullable|exists:coupons,id',
        ]);

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

        $amountPaise = (int) round($amount * 100);

        if ($amountPaise > 0 && !config('services.subscriptions.purchases_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Paid subscriptions are temporarily unavailable while payment setup is being completed.',
            ], 503);
        }
        
        if ($amountPaise <= 0) {
            return response()->json([
                'order_id' => 'free_plan_' . time(),
                'amount' => 0,
                'key' => null
            ]);
        }

        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');
        $api = new Api($keyId, $keySecret);

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
        // Temporary no-gateway checkout reuses this established POST endpoint.
        // This keeps checkout working during deployments where an older route
        // cache does not yet contain the dedicated purchase route.
        if ($request->boolean('temporary_checkout')) {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
            ]);

            $plan = Plan::query()
                ->where('status', 1)
                ->findOrFail($validated['plan_id']);

            return $this->purchaseWithoutGateway($request, $plan);
        }

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'nullable|string',
            'razorpay_signature' => 'nullable|string',
        ]);

        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');
        
        $plan = Plan::query()->where('status', 1)->findOrFail($validated['plan_id']);
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

        if ($amountPaid > 0 && !config('services.subscriptions.purchases_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Paid subscriptions are temporarily unavailable while payment setup is being completed.',
            ], 503);
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
                    'credits' => $subscription->total_credits === null
                        ? 'Unlimited'
                        : number_format($subscription->total_credits)
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

                    // 1. Handle Top-up Payment (One-time credit purchase)
                    if (isset($payment['notes']['type']) && $payment['notes']['type'] === 'topup') {
                        $this->processTopup($payment);
                        return response()->json(['success' => true]);
                    }

                    // 2. Handle Plan Subscription Payment
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
        $freePlan = Plan::query()
            ->where('amount', 0)
            ->orderByRaw("CASE WHEN name = 'Free Developer' THEN 0 ELSE 1 END")
            ->first();

        DB::transaction(function () use ($user, $freePlan): void {
            if ($freePlan) {
                $this->assignments->assign($user, $freePlan, [
                    'razorpay_order_id' => 'free-' . $user->id . '-' . Str::lower(Str::random(12)),
                    'expires_at' => now()->addYears(10),
                ]);

                return;
            }

            $subscription = Subscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($subscription) {
                $subscription->forceFill([
                    'status' => 'expired',
                    'expires_at' => now(),
                    'available_credits' => 0,
                ])->save();
            }

            $user->forceFill([
                'plan_id' => null,
                'available_credits' => 0,
            ])->save();
        });
    }

    private function activateSubscription($user, $plan, $orderId, $paymentId, $signature, $amountPaid, $discountAmount, $remainingCycles, $couponId = null, $transactionType = 'purchase')
    {
        return DB::transaction(function() use ($user, $plan, $orderId, $paymentId, $signature, $amountPaid, $discountAmount, $remainingCycles, $couponId, $transactionType) {
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

            $creditsToAdd = $plan->api_hits_limit;

            $subscription = $this->assignments->assign($user, $plan, [
                'coupon_id' => $couponId,
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
                'amount_paid' => $amountPaid,
                'discount_amount' => $discountAmount,
                'remaining_discount_cycles' => $remainingCycles,
                'expires_at' => $expiresAt,
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
                'type' => $transactionType,
                'credits' => $creditsToAdd ?? 0,
            ]);

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

    public function createTopupOrder(Request $request)
    {
        if (!config('services.subscriptions.purchases_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Paid subscription purchases are temporarily unavailable.',
            ], 503);
        }

        $user = Auth::user();
        $subscription = $user->subscriptions()->where('status', 'active')->latest()->first();
        
        // Validation: Only if credits are 0
        if ($subscription && $subscription->available_credits > 0) {
            return response()->json(['success' => false, 'message' => 'Top-up is only available when credits are exhausted.'], 400);
        }

        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');

        $api = new Api($keyId, $keySecret);
        
        $amount = 100; // Rs. 100
        $amountPaise = $amount * 100;

        $orderData = [
            'receipt'         => 'topup_' . Auth::id() . '_' . time(),
            'amount'          => $amountPaise,
            'currency'        => 'INR',
            'payment_capture' => 1,
            'notes'           => [
                'type' => 'topup',
                'user_id' => $user->id,
                'credits' => 20000
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

    public function verifyTopupPayment(Request $request)
    {
        if (!config('services.subscriptions.purchases_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Paid subscription purchases are temporarily unavailable.',
            ], 503);
        }

        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');
        
        $orderId = $request->razorpay_order_id;
        $paymentId = $request->razorpay_payment_id;
        $signature = $request->razorpay_signature;

        $api = new Api($keyId, $keySecret);
        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];
            $api->utility->verifyPaymentSignature($attributes);
        } catch (SignatureVerificationError $e) {
            return response()->json(['success' => false, 'message' => 'Payment verification failed: ' . $e->getMessage()], 400);
        }

        try {
            DB::transaction(function() use ($orderId, $paymentId) {
                $user = Auth::user();
                $subscription = $user->subscriptions()->where('status', 'active')->latest()->first();
                
                if (!$subscription) {
                    throw new \Exception('No active subscription found to top up.');
                }

                $creditsToAdd = 20000;
                
                // Update subscription
                $subscription->increment('available_credits', $creditsToAdd);
                $subscription->increment('total_credits', $creditsToAdd);

                // Update user
                $user->increment('available_credits', $creditsToAdd);

                // Record transaction
                TransactionHistory::create([
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'razorpay_payment_id' => $paymentId,
                    'razorpay_order_id' => $orderId,
                    'amount' => 100,
                    'plan_name' => 'Credit Top-up',
                    'billing_cycle' => 'one-time',
                    'status' => 'success',
                    'type' => 'topup',
                    'credits' => $creditsToAdd
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Top-up successful! 20,000 credits added.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
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

    private function processTopup($payment)
    {
        DB::transaction(function() use ($payment) {
            $user = User::where('email', $payment['email'])->first();
            if (!$user) return;

            $subscription = $user->subscriptions()->where('status', 'active')->latest()->first();
            if (!$subscription) return;

            // Check if this top-up was already processed
            if (TransactionHistory::where('razorpay_payment_id', $payment['id'])->exists()) {
                return;
            }

            $creditsToAdd = $payment['notes']['credits'] ?? 20000;
            
            // Update subscription
            $subscription->increment('available_credits', $creditsToAdd);
            $subscription->increment('total_credits', $creditsToAdd);

            // Update user
            $user->increment('available_credits', $creditsToAdd);

            // Record transaction
            TransactionHistory::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'razorpay_payment_id' => $payment['id'],
                'razorpay_order_id' => $payment['order_id'],
                'amount' => $payment['amount'] / 100,
                'plan_name' => 'Credit Top-up',
                'billing_cycle' => 'one-time',
                'status' => 'success',
                'type' => 'topup',
                'credits' => $creditsToAdd
            ]);
        });
    }
}
