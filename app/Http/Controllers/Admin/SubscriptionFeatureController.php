<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionFeature;
use Illuminate\Http\Request;

class SubscriptionFeatureController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = SubscriptionFeature::query();

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('key', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $total = SubscriptionFeature::count();
            $filtered = $query->count();
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;

            $features = $query->orderBy('name')
                ->skip($start)
                ->take($limit)
                ->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $features,
            ]);
        }

        return view('subscription-features.index');
    }

    public function create()
    {
        return view('subscription-features.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:subscription_features,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        SubscriptionFeature::create($validated);

        return redirect()->route('subscription-features.index')->with('success', 'Subscription feature created successfully.');
    }

    public function edit(SubscriptionFeature $subscriptionFeature)
    {
        return view('subscription-features.edit', compact('subscriptionFeature'));
    }

    public function update(Request $request, SubscriptionFeature $subscriptionFeature)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:subscription_features,key,' . $subscriptionFeature->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $subscriptionFeature->update($validated);

        return redirect()->route('subscription-features.index')->with('success', 'Subscription feature updated successfully.');
    }

    public function destroy(SubscriptionFeature $subscriptionFeature)
    {
        $subscriptionFeature->delete();

        return redirect()->route('subscription-features.index')->with('success', 'Subscription feature deleted successfully.');
    }

    public function toggleStatus(SubscriptionFeature $subscriptionFeature)
    {
        $subscriptionFeature->is_active = !$subscriptionFeature->is_active;
        $subscriptionFeature->save();

        return response()->json([
            'success' => true,
            'is_active' => $subscriptionFeature->is_active,
            'message' => 'Subscription feature status updated successfully.',
        ]);
    }
}
