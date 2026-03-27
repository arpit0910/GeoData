<?php

namespace App\Http\Controllers;

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
        return view('subscriptions.pricing', compact('plans'));
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
            'payment_capture' => 1
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
                $remainingCycles = $coupon->apply_to_cycles - 1; // First cycle applied now
            }
        }

        $orderId = $request->razorpay_order_id;
        $paymentId = $request->razorpay_payment_id;
        $signature = $request->razorpay_signature;

        // If it's a free plan, skip signature verification
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

        // Calculate expiration date
        $expiresAt = now();
        if ($plan->billing_cycle === 'monthly') {
            $expiresAt->addMonth();
        } elseif ($plan->billing_cycle === 'yearly') {
            $expiresAt->addYear();
        } elseif ($plan->billing_cycle === 'lifetime') {
            $expiresAt->addYears(100);
        }

        // Infer absolute credits allocation limit
        $creditsToAdd = $plan->api_hits_limit ?? 999999999;

        // Record the physical subscription and credit bounds
        $subscription = Subscription::create([
            'user_id' => Auth::id(),
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

        if ($couponId) {
            $coupon = Coupon::find($couponId);
            if ($coupon) {
                $coupon->increment('used_count');
                $coupon->users()->attach(Auth::id(), ['subscription_id' => $subscription->id]);
            }
        }

        // Record Transaction History
        TransactionHistory::create([
            'user_id' => Auth::id(),
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'coupon_id' => $couponId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_order_id' => $orderId,
            'amount' => $amountPaid,
            'discount_amount' => $discountAmount,
            'coupon_code' => $coupon ? $coupon->code : null,
            'plan_name' => $plan->name,
            'billing_cycle' => $plan->billing_cycle,
            'status' => 'success',
        ]);

        // Update User records context purely
        $user = Auth::user();
        $user->plan_id = $plan->id;
        $user->save();
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

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => TransactionHistory::where('user_id', Auth::id())->count(),
                'recordsFiltered' => $total,
                'data' => $transactions
            ]);
        }

        $transactions = TransactionHistory::where('user_id', Auth::id())
            ->with(['plan', 'coupon'])
            ->latest()
            ->paginate(15);

        return view('subscriptions.transactions', compact('transactions'));
    }
}
