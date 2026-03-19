<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;

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
}
