<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class SubscriptionController extends Controller
{
    public function pricing()
    {
        $plans = Plan::where('status', 1)->get();
        return view('subscriptions.pricing', compact('plans'));
    }

    public function createOrder(Request $request, Plan $plan)
    {
        // Require dummy keys or real keys to be set in .env
        $keyId = env('RAZORPAY_KEY', 'rzp_test_dummy');
        $keySecret = env('RAZORPAY_SECRET', 'dummy_secret');

        $api = new Api($keyId, $keySecret);
        
        // Amount should be in paise (multiply by 100)
        $amount = ($plan->amount - $plan->discount_amount) * 100;
        
        if ($amount <= 0) {
            // Handle free plans (no razorpay call needed)
            return response()->json([
                'order_id' => 'free_plan_' . time(),
                'amount' => 0,
                'key' => $keyId
            ]);
        }

        $orderData = [
            'receipt'         => 'rcpt_' . Auth::id() . '_' . time(),
            'amount'          => $amount,
            'currency'        => 'INR',
            'payment_capture' => 1
        ];

        try {
            $razorpayOrder = $api->order->create($orderData);
            return response()->json([
                'order_id' => $razorpayOrder['id'],
                'amount' => $amount,
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

        // Record the subscription
        Subscription::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
            'amount_paid' => $amountPaid,
            'status' => 'active',
            'expires_at' => $expiresAt,
        ]);

        // Update User records and assign credits
        $user = Auth::user();
        $user->plan_id = $plan->id;
        // If unlimited limit, give 100 million credits or something functional
        $creditsToAdd = $plan->api_hits_limit ?? 999999999;
        $user->available_credits = $user->available_credits + $creditsToAdd;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully!',
            'redirect' => route('pricing') // You can redirect to dashboard later
        ]);
    }
}
