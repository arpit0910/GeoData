<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Subscription;
use App\Models\TransactionHistory;
use App\Models\Plan;
use App\Services\SubscriptionAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function assignPlan(
        Request $request,
        Subscription $subscription,
        SubscriptionAssignmentService $assignments
    )
    {
        $validated = $request->validate([
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where(fn ($query) => $query->where('status', 1)),
            ],
        ]);
        $plan = Plan::findOrFail($validated['plan_id']);

        DB::transaction(function () use ($subscription, $plan, $assignments) {
            $credits = $plan->api_hits_limit;
            $reference = 'admin-manual-' . $subscription->user_id . '-' . Str::lower(Str::random(10));

            $newSubscription = $assignments->assign($subscription->user, $plan, [
                'razorpay_order_id' => $reference,
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
                'credits' => $credits ?? 0,
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
            $creditsToAdd = (int) $request->credits;
            DB::transaction(function () use ($subscription, $creditsToAdd): void {
                $locked = Subscription::query()->with(['user', 'plan'])
                    ->lockForUpdate()
                    ->findOrFail($subscription->id);

                if ($locked->plan && $locked->plan->api_hits_limit === null) {
                    throw new \DomainException('This subscription already has unlimited credits.');
                }

                $locked->forceFill([
                    'total_credits' => (int) $locked->total_credits + $creditsToAdd,
                    'available_credits' => (int) $locked->available_credits + $creditsToAdd,
                ])->save();
                $locked->user->forceFill([
                    'available_credits' => $locked->available_credits,
                ])->save();

                TransactionHistory::create([
                    'user_id' => $locked->user_id,
                    'subscription_id' => $locked->id,
                    'plan_id' => $locked->plan_id,
                    'amount' => 0,
                    'status' => 'success',
                    'type' => 'credit',
                    'credits' => $creditsToAdd,
                    'plan_name' => $locked->plan?->name ?: 'Manual Credit',
                    'billing_cycle' => $locked->plan?->billing_cycle,
                ]);
            });

            return response()->json([
                'status' => true,
                'message' => "Successfully assigned {$creditsToAdd} credits to the account."
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign credits: ' . $e->getMessage()
            ], 500);
        }
    }
}
