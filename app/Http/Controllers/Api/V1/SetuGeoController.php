<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\CurrencyConversion;
use App\Models\State;
use App\Models\Pincode;
use App\Models\Region;
use App\Models\SubRegion;
use App\Models\Timezone;
use App\Models\Bank;
use App\Models\BankBranch;
use Illuminate\Http\Request;

class SetuGeoController extends Controller
{
    /**
     * Retrieve a list of generic Regions
     */
    public function regions(Request $request)
    {
        $query = Region::query();

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        $regions = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $regions->items(),
            'meta' => [
                'current_page' => $regions->currentPage(),
                'last_page' => $regions->lastPage(),
                'total' => $regions->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of SubRegions
     */
    public function subregions(Request $request)
    {
        $query = SubRegion::query();

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        if ($request->has('region_id')) {
            $query->where('region_id', $request->query('region_id'));
        } elseif ($request->has('region_name')) {
            $query->whereHas('Region', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('region_name') . '%');
            });
        }

        $subregions = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $subregions->items(),
            'meta' => [
                'current_page' => $subregions->currentPage(),
                'last_page' => $subregions->lastPage(),
                'total' => $subregions->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of global Timezones
     */
    public function timezones(Request $request)
    {
        $query = Timezone::query();

        if ($request->has('zone_name')) {
            $query->where('zone_name', 'LIKE', '%' . $request->query('zone_name') . '%');
        } elseif ($request->has('name')) {
            $query->where('zone_name', 'LIKE', '%' . $request->query('name') . '%');
        }

        $timezones = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $timezones->items(),
            'meta' => [
                'current_page' => $timezones->currentPage(),
                'last_page' => $timezones->lastPage(),
                'total' => $timezones->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of countries dynamically filtered
     */
    public function countries(Request $request)
    {
        $query = Country::select('id', 'name', 'iso2', 'iso3', 'phonecode', 'currency', 'capital', 'subregion_id', 'region_id');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }
        if ($request->has('iso2')) {
            $query->where('iso2', strtoupper($request->query('iso2')));
        }
        if ($request->has('iso3')) {
            $query->where('iso3', strtoupper($request->query('iso3')));
        }
        if ($request->has('region_id')) {
            $query->where('region_id', $request->query('region_id'));
        }
        if ($request->has('subregion_id')) {
            $query->where('subregion_id', $request->query('subregion_id'));
        }
            
        $countries = $query->paginate($request->query('limit', 100));
            
        return response()->json([
            'success' => true,
            'data' => $countries->items(),
            'meta' => [
                'current_page' => $countries->currentPage(),
                'last_page' => $countries->lastPage(),
                'total' => $countries->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of states dynamically filtered
     */
    public function states(Request $request)
    {
        $query = State::select('id', 'name', 'country_id', 'latitude', 'longitude');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        if ($request->has('country_id')) {
            $query->where('country_id', $request->query('country_id'));
        } elseif ($request->has('country_name')) {
            $query->whereHas('Country', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('country_name') . '%');
            });
        }

        $states = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $states->items(),
            'meta' => [
                'current_page' => $states->currentPage(),
                'last_page' => $states->lastPage(),
                'total' => $states->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of cities dynamically filtered
     */
    public function cities(Request $request)
    {
        $query = City::select('id', 'name', 'state_id', 'country_id', 'latitude', 'longitude');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        // Filter by State
        if ($request->has('state_id')) {
            $query->where('state_id', $request->query('state_id'));
        } elseif ($request->has('state_name')) {
            $query->whereHas('State', function($q) use ($request) { 
                $q->where('name', 'LIKE', '%' . $request->query('state_name') . '%');
            });
        }

        // Filter by Country
        if ($request->has('country_id')) {
            $query->where('country_id', $request->query('country_id'));
        } elseif ($request->has('country_name')) {
            $query->whereHas('Country', function($q) use ($request) { 
                $q->where('name', 'LIKE', '%' . $request->query('country_name') . '%');
            });
        }

        $cities = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $cities->items(),
            'meta' => [
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'total' => $cities->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve a list of pincodes dynamically filtered
     */
    public function pincodes(Request $request)
    {
        $query = Pincode::query(); 

        if ($request->has('pincode')) {
            $query->where('postal_code', 'LIKE', '%' . $request->query('pincode') . '%');
        }

        // Filter by city
        if ($request->has('city_id')) {
            $query->where('city_id', $request->query('city_id'));
        } elseif ($request->has('city_name')) {
            $query->whereHas('city', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('city_name') . '%');
            });
        }

        // Filter by state
        if ($request->has('state_id')) {
            $query->where('state_id', $request->query('state_id'));
        } elseif ($request->has('state_name')) {
            $query->whereHas('state', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('state_name') . '%');
            });
        }

        // Filter by country
        if ($request->has('country_id')) {
            $query->where('country_id', $request->query('country_id'));
        } elseif ($request->has('country_name')) {
            $query->whereHas('country', function($q) use ($request) {
                $q->where('name', 'LIKE', '%' . $request->query('country_name') . '%');
            });
        }

        $pincodes = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $pincodes->items(),
            'meta' => [
                'current_page' => $pincodes->currentPage(),
                'last_page' => $pincodes->lastPage(),
                'total' => $pincodes->total(),
            ]
        ], 200);
    }

    /**
     * Comprehensive search for a specific pincode linking its detailed hierarchy
     */
    public function pincodeSearch(Request $request)
    {
        $code = $request->query('pincode', $request->query('code'));
        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Please provide a pincode parameter (e.g. ?pincode=123456).'], 400);
        }

        $pincodes = Pincode::with(['state', 'city', 'country'])
            ->where('postal_code', $code)
            ->get();
            
        if ($pincodes->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Pincode not found'], 404);
        }

        $data = $pincodes->map(function ($pincode) {
            return [
                'pincode' => $pincode->postal_code ?? $pincode->pincode,
                'country' => $pincode->country ? [
                    'id' => $pincode->country->id,
                    'name' => $pincode->country->name,
                    'iso2' => $pincode->country->iso2 ?? null,
                    'currency' => $pincode->country->currency ?? null,
                ] : null,
                'state' => $pincode->state ? [
                    'id' => $pincode->state->id,
                    'name' => $pincode->state->name,
                    'state_code' => $pincode->state->state_code ?? null,
                ] : null,
                'city' => $pincode->city ? [
                    'id' => $pincode->city->id,
                    'name' => $pincode->city->name,
                ] : null,
                'latitude' => $pincode->latitude,
                'longitude' => $pincode->longitude,
                'county' => $pincode->county,
                'accuracy' => $pincode->accuracy,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    /**
     * Retrieve the active user's current API usage and credit balance.
     */
    public function usage(Request $request)
    {
        $user = $request->user();
        
        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No active subscription found.'], 404);
        }

        $logs = $user->apiLogs()->latest()->limit(10)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'plan_name' => $subscription->plan ? $subscription->plan->name : 'N/A',
                'total_credits' => $subscription->total_credits,
                'used_credits' => $subscription->used_credits,
                'available_credits' => $subscription->available_credits,
                'expires_at' => $subscription->expires_at,
                'recent_logs' => $logs->map(function($log) {
                    return [
                        'endpoint' => $log->endpoint,
                        'status' => $log->status_code,
                        'method' => $log->method,
                        'time' => $log->created_at->format('Y-m-d H:i:s'),
                        'credited' => $log->credit_deducted
                    ];
                })
            ]
        ], 200);
    }

    /**
     * Retrieve currency conversion rates for a specific currency code (against USD and INR).
     */
    public function currencyExchange(Request $request)
    {
        $currencyCode = $request->query('currency');
        if (!$currencyCode) {
            return response()->json(['success' => false, 'message' => 'Please provide a currency parameter (e.g. ?currency=EUR).'], 400);
        }

        $currency = strtoupper($currencyCode);

        // Fetch based on currency code
        $conversion = CurrencyConversion::where('currency', $currency)->first();

        if (!$conversion) {
            return response()->json(['success' => false, 'message' => 'Currency exchange rate not found for ' . $currency . '. We currently support 30+ major currencies.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'base_currency' => $conversion->currency,
                'exchange_rates' => [
                    'USD' => $conversion->usd_conversion_rate,
                    'INR' => $conversion->inr_conversion_rate
                ],
                'last_updated' => $conversion->updated_at->toDateTimeString(),
                'provider' => 'SetuGeo Financial Engine'
            ]
        ], 200);
    }
    /**
     * Retrieve all unique banks
     */
    public function banks(Request $request)
    {
        $query = Bank::query();
        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }
        $banks = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $banks->items(),
            'meta' => [
                'current_page' => $banks->currentPage(),
                'last_page' => $banks->lastPage(),
                'total' => $banks->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve all branches of a particular bank
     */
    public function bankBranches(Bank $bank, Request $request)
    {
        $query = $bank->branches()->with(['city', 'state']);
        $branches = $query->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $branches->items(),
            'meta' => [
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'total' => $branches->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve details of a specific branch by IFSC code
     */
    public function branchInfo($ifsc)
    {
        $branch = BankBranch::with(['bank', 'city', 'state'])->where('ifsc', $ifsc)->first();

        if (!$branch) {
            return response()->json(['success' => false, 'message' => 'Branch with IFCS ' . $ifsc . ' not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $branch
        ], 200);
    }

    /**
     * Retrieve all banks having branches in a particular city
     */
    public function banksInCity(City $city, Request $request)
    {
        $bankIds = BankBranch::where('city_id', $city->id)->pluck('bank_id')->unique();
        $banks = Bank::whereIn('id', $bankIds)->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $banks->items(),
            'meta' => [
                'current_page' => $banks->currentPage(),
                'last_page' => $banks->lastPage(),
                'total' => $banks->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve all banks having branches in a particular state
     */
    public function banksInState(State $state, Request $request)
    {
        $bankIds = BankBranch::where('state_id', $state->id)->pluck('bank_id')->unique();
        $banks = Bank::whereIn('id', $bankIds)->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $banks->items(),
            'meta' => [
                'current_page' => $banks->currentPage(),
                'last_page' => $banks->lastPage(),
                'total' => $banks->total(),
            ]
        ], 200);
    }
}
