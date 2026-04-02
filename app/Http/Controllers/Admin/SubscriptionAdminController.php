<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Subscription;
use App\Models\TransactionHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('subscriptions.admin.index');
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan']);
        return view('subscriptions.admin.show', compact('subscription'));
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
