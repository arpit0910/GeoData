<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Subscription;
use App\Models\TransactionHistory;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionAdminController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Subscription::with(['user', 'plan']);

            // Handle search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('plan', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhere('razorpay_order_id', 'like', "%{$search}%");
            }

            // Total records before filtering
            $total = Subscription::count();
            
            // Filtered records count
            $filtered = $query->count();
            
            // Pagination
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            // Fetch data
            $subscriptions = $query->skip($start)->take($limit)->orderBy('id', 'desc')->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $subscriptions
            ]);
        }

        $plans = Plan::where('status', 1)->orderBy('amount')->orderBy('name')->get();
        return view('subscriptions.admin.index', compact('plans'));
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan']);
        return view('subscriptions.admin.show', compact('subscription'));
    }

    public function assignPlan(Request $request, Subscription $subscription)
    {
        $validated = $request->validate(['plan_id' => 'required|exists:plans,id']);
        $plan = Plan::findOrFail($validated['plan_id']);

        DB::transaction(function () use ($subscription, $plan) {
            Subscription::where('user_id', $subscription->user_id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $expiresAt = match ($plan->billing_cycle) {
                'monthly' => now()->addMonth(),
                'yearly' => now()->addYear(),
                default => now()->addYears(100),
            };
            $credits = $plan->api_hits_limit ?? 999999999;
            $reference = 'admin-manual-' . $subscription->user_id . '-' . Str::lower(Str::random(10));

            $newSubscription = Subscription::create([
                'user_id' => $subscription->user_id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $reference,
                'amount_paid' => 0,
                'discount_amount' => 0,
                'remaining_discount_cycles' => 0,
                'status' => 'active',
                'expires_at' => $expiresAt,
                'total_credits' => $credits,
                'used_credits' => 0,
                'available_credits' => $credits,
                'last_credit_refresh' => now(),
            ]);

            $newSubscription->user()->update([
                'plan_id' => $plan->id,
                'available_credits' => $credits,
                'status' => 1,
            ]);

            TransactionHistory::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $newSubscription->id,
                'plan_id' => $plan->id,
                'razorpay_order_id' => $reference,
                'amount' => 0,
                'discount_amount' => 0,
                'plan_name' => $plan->name,
                'billing_cycle' => $plan->billing_cycle,
                'status' => 'success',
                'type' => 'admin_assignment',
                'credits' => $credits,
            ]);
        });

        return response()->json(['status' => true, 'message' => "{$plan->name} assigned successfully."]);
    }

    public function assignCredits(Request $request, Subscription $subscription)
    {
        $request->validate([
            'credits' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $creditsToAdd = $request->credits;

            // Update subscription credits
            $subscription->increment('total_credits', $creditsToAdd);
            $subscription->increment('available_credits', $creditsToAdd);

            // Record transaction
            TransactionHistory::create([
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'amount' => 0,
                'status' => 'completed',
                'type' => 'credit',
                'credits' => $creditsToAdd,
                'plan_name' => $subscription->plan ? $subscription->plan->name : 'Manual Credit',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Successfully assigned {$creditsToAdd} credits to the account."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign credits: ' . $e->getMessage()
            ], 500);
        }
    }
}
