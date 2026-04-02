<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Plan::query();

            // Handle search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where('name', 'like', "%{$search}%");
            }

            // Total records before filtering
            $total = Plan::count();
            
            // Filtered records count
            $filtered = $query->count();
            
            // Pagination
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            // Fetch data
            $plans = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $plans
            ]);
        }

        return view('plans.index');
    }

    public function create()
    {
        return view('plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gateway_product_id' => 'nullable|string|max:255',
            'api_hits_limit' => 'nullable|integer|min:0',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly,lifetime',
            'terms' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'nullable|string|max:255',
        ]);

        $data = $request->all();
        $data['status'] = 1; // Default to active when created
        
        if (isset($data['benefits']) && is_array($data['benefits'])) {
            $data['benefits'] = array_values(array_filter($data['benefits']));
        }

        $plan = Plan::create($data);

        // Optional immediate sync if the user requested it and they haven't provided a manual product ID
        if ($request->sync_now == '1' && empty($plan->gateway_product_id) && $plan->billing_cycle !== 'lifetime') {
            try {
                $plan->syncWithRazorpay();
                $message = 'Plan created and synced with Razorpay successfully.';
            } catch (\Exception $e) {
                $message = 'Plan created locally, but Razorpay sync failed: ' . $e->getMessage();
                return redirect()->route('plans.index')->with('warning', $message);
            }
        } else {
            $message = 'Plan created successfully.';
        }

        return redirect()->route('plans.index')->with('success', $message);
    }

    public function edit(Plan $plan)
    {
        return view('plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gateway_product_id' => 'nullable|string|max:255',
            'api_hits_limit' => 'nullable|integer|min:0',
            'amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|string|in:monthly,yearly,lifetime',
            'terms' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['status']);
        
        if (isset($data['benefits']) && is_array($data['benefits'])) {
            $data['benefits'] = array_values(array_filter($data['benefits']));
        } else {
            $data['benefits'] = [];
        }

        $plan->update($data);

        return redirect()->route('plans.index')->with('success', 'Plan updated successfully.');
    }

    public function toggleStatus(Plan $plan)
    {
        $plan->status = !$plan->status;
        $plan->save();
        
        return response()->json([
            'success' => true,
            'status' => $plan->status,
            'message' => 'Plan status updated successfully'
        ]);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return redirect()->route('plans.index')->with('success', 'Plan deleted successfully.');
    }

    public function syncToGateway(Plan $plan)
    {
        try {
            $plan->syncWithRazorpay();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan synced with Razorpay successfully! Plan ID: ' . $plan->gateway_product_id,
                    'gateway_id' => $plan->gateway_product_id
                ]);
            }
            
            return redirect()->back()->with('success', 'Plan synced with Razorpay successfully!');
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sync failed: ' . $e->getMessage()
                ], 422);
            }
            
            return redirect()->back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}
