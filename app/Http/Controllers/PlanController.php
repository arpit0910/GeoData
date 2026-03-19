<?php

namespace App\Http\Controllers;

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

        Plan::create($data);

        return redirect()->route('plans.index')->with('success', 'Plan created successfully.');
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
}
