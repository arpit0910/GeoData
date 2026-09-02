<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionFeature;
use Illuminate\Http\Request;

class ApiAccessController extends Controller
{
    public function index(Request $request)
    {
        $subscription = $request->user()->subscriptions()
            ->with('plan.features')
            ->active()
            ->unexpired()
            ->latest()
            ->first();

        $plan = $subscription?->plan;
        $canUse = fn (string $feature): bool => $plan?->hasFeature($feature) ?? false;
        $isFreePlan = $plan && (float) $plan->amount <= 0;
        $availableApiDocs = [];

        if ($isFreePlan || $canUse(SubscriptionFeature::MODULE_INDIA_PINCODE_API)) {
            $availableApiDocs[] = [
                'category' => 'India Pincode', 'icon' => 'fa-map-pin', 'color' => 'emerald',
                'items' => [[
                    'method' => 'GET', 'path' => '/api/v1/india/pincode/{pincode}',
                    'description' => 'Fetch one Indian pincode as a single object.', 'anchor' => 'india-pincode',
                    'parameters' => [['name' => 'pincode', 'location' => 'Path', 'required' => true, 'description' => 'Six-digit Indian postal code, for example 400001.']],
                    'example' => "curl --request GET \"" . url('/api/v1/india/pincode/400001') . "\"\n  --header \"Authorization: Bearer YOUR_API_TOKEN\"",
                    'response' => ['success' => true, 'data' => ['pincode' => '400001', 'country' => 'India', 'country_code' => 'IN', 'state' => 'Maharashtra', 'state_code' => 'MH', 'city' => 'Mumbai', 'area' => 'Fort', 'latitude' => '18.9398', 'longitude' => '72.8354']],
                ]],
            ];
        }

        if ($isFreePlan || $canUse(SubscriptionFeature::MODULE_IFSC_API)) {
            $availableApiDocs[] = [
                'category' => 'IFSC Lookup', 'icon' => 'fa-university', 'color' => 'blue',
                'items' => [[
                    'method' => 'GET', 'path' => '/api/v1/bank/ifsc/{ifsc}',
                    'description' => 'Fetch bank branch details using an IFSC code.', 'anchor' => 'branch-info',
                    'parameters' => [['name' => 'ifsc', 'location' => 'Path', 'required' => true, 'description' => 'The bank IFSC code, for example SBIN0000001.']],
                    'example' => "curl --request GET \"" . url('/api/v1/bank/ifsc/SBIN0000001') . "\"\n  --header \"Authorization: Bearer YOUR_API_TOKEN\"",
                    'response' => ['success' => true, 'data' => ['ifsc' => 'SBIN0000001', 'branch' => 'Fort Branch', 'bank' => ['name' => 'State Bank of India'], 'address' => 'D N Road, Fort, Mumbai']],
                ]],
            ];
        }

        return view('available-apis.index', compact('subscription', 'plan', 'availableApiDocs'));
    }
}
