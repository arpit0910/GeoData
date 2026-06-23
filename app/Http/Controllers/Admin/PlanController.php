<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Benefit;
use App\Models\Plan;
use App\Models\SubscriptionFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PlanController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Plan::query();

            if ($this->featureTablesReady()) {
                $query->with('features');
            }

            if ($this->benefitTablesReady()) {
                $query->with('benefitItems');
            }

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
        $features = $this->featureTablesReady()
            ? SubscriptionFeature::orderBy('name')->get()
            : collect();
        $benefits = $this->benefitTablesReady()
            ? Benefit::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('plans.create', compact('features', 'benefits'));
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
            'benefit_ids' => 'nullable|array',
            'benefit_ids.*' => 'integer|exists:benefits,id',
            'feature_ids' => 'nullable|array',
            'feature_ids.*' => 'integer|exists:subscription_features,id',
        ]);

        $data = $request->all();
        $data['status'] = 1; // Default to active when created
        $data['benefits'] = $this->selectedBenefitNames($request);

        $plan = Plan::create($data);

        if ($this->featureTablesReady()) {
            $plan->features()->sync($request->input('feature_ids', []));
        }

        if ($this->benefitTablesReady()) {
            $plan->benefitItems()->sync($request->input('benefit_ids', []));
        }

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
        if ($this->featureTablesReady()) {
            $plan->load('features');
        }
        if ($this->benefitTablesReady()) {
            $plan->load('benefitItems');
        }

        $features = $this->featureTablesReady()
            ? SubscriptionFeature::orderBy('name')->get()
            : collect();
        $benefits = $this->benefitTablesReady()
            ? Benefit::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        return view('plans.edit', compact('plan', 'features', 'benefits'));
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
            'benefit_ids' => 'nullable|array',
            'benefit_ids.*' => 'integer|exists:benefits,id',
            'feature_ids' => 'nullable|array',
            'feature_ids.*' => 'integer|exists:subscription_features,id',
        ]);

        $data = $request->except(['status']);
        $data['benefits'] = $this->selectedBenefitNames($request);

        $plan->update($data);

        if ($this->featureTablesReady()) {
            $plan->features()->sync($request->input('feature_ids', []));
        }

        if ($this->benefitTablesReady()) {
            $plan->benefitItems()->sync($request->input('benefit_ids', []));
        }

        return redirect()->route('plans.index')->with('success', 'Plan updated successfully.');
    }

    protected function featureTablesReady(): bool
    {
        return Schema::hasTable('subscription_features')
            && Schema::hasTable('plan_subscription_feature');
    }

    protected function benefitTablesReady(): bool
    {
        return Schema::hasTable('benefits')
            && Schema::hasTable('benefit_plan');
    }

    protected function selectedBenefitNames(Request $request): array
    {
        if (!$this->benefitTablesReady()) {
            return [];
        }

        return Benefit::query()
            ->whereIn('id', $request->input('benefit_ids', []))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();
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
