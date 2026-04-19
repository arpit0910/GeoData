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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

        $limit = max(1, intval($request->query('limit', 100)));
        $regions = $query->paginate($limit);

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

        $limit = max(1, intval($request->query('limit', 100)));
        $subregions = $query->paginate($limit);

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

        $limit = max(1, intval($request->query('limit', 100)));
        $timezones = $query->paginate($limit);

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
            
        $limit = max(1, intval($request->query('limit', 100)));
        $countries = $query->paginate($limit);
            
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

        $limit = max(1, intval($request->query('limit', 100)));
        $states = $query->paginate($limit);

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

        $limit = max(1, intval($request->query('limit', 100)));
        $cities = $query->paginate($limit);

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

        $limit = max(1, intval($request->query('limit', 100)));
        $pincodes = $query->paginate($limit);

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
                'area' => $pincode->area,
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
     * Retrieve all banks operating in a specific country.
     */
    public function countryBanks(Country $country, Request $request)
    {
        $bankIds = BankBranch::whereHas('city', function($q) use ($country) {
            $q->where('country_id', $country->id);
        })->pluck('bank_id')->unique();
        $banks = Bank::whereIn('id', $bankIds)->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $banks->items(),
            'meta' => [
                'country' => $country->name,
                'current_page' => $banks->currentPage(),
                'last_page' => $banks->lastPage(),
                'total' => $banks->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve all banks having branches in a specific pincode area.
     */
    public function pincodeBanks($pincode, Request $request)
    {
        // We match by postal_code since pincode records might be multiple (areas)
        $cityIds = Pincode::where('postal_code', $pincode)->pluck('city_id')->unique();
        
        $bankIds = BankBranch::whereIn('city_id', $cityIds)->pluck('bank_id')->unique();
        $banks = Bank::whereIn('id', $bankIds)->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $banks->items(),
            'meta' => [
                'pincode' => $pincode,
                'current_page' => $banks->currentPage(),
                'last_page' => $banks->lastPage(),
                'total' => $banks->total(),
            ]
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

    /**
     * Retrieve full details of a single country including region, sub-region, and timezones.
     */
    public function countryDetail(Country $country)
    {
        $country->load(['Region', 'SubRegion', 'timezones']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2' => $country->iso2,
                'iso3' => $country->iso3,
                'numeric_code' => $country->numeric_code,
                'phonecode' => $country->phonecode,
                'capital' => $country->capital,
                'currency' => $country->currency,
                'currency_name' => $country->currency_name,
                'currency_symbol' => $country->currency_symbol,
                'tld' => $country->tld,
                'native' => $country->native,
                'nationality' => $country->nationality,
                'latitude' => $country->latitude,
                'longitude' => $country->longitude,
                'emoji' => $country->emoji,
                'population' => $country->population,
                'gdp' => $country->gdp,
                'area_sq_km' => $country->area_sq_km,
                'income_level' => $country->income_level,
                'driving_side' => $country->driving_side,
                'measurement_system' => $country->measurement_system,
                'tax_system' => $country->tax_system,
                'standard_tax_rate' => $country->standard_tax_rate,
                'is_oecd' => $country->is_oecd,
                'is_eu' => $country->is_eu,
                'dialing' => [
                    'phonecode' => $country->phonecode,
                    'international_prefix' => $country->international_prefix,
                    'trunk_prefix' => $country->trunk_prefix,
                    'max_mobile_digits' => $country->max_mobile_digits,
                ],
                'postal_code' => [
                    'format' => $country->postal_code_format,
                    'regex' => $country->postal_code_regex,
                ],
                'region' => $country->Region ? [
                    'id' => $country->Region->id,
                    'name' => $country->Region->name,
                ] : null,
                'sub_region' => $country->SubRegion ? [
                    'id' => $country->SubRegion->id,
                    'name' => $country->SubRegion->name,
                ] : null,
                'timezones' => $country->timezones()->get()->map(fn($tz) => [
                    'zone_name' => $tz->zone_name,
                    'gmt_offset_name' => $tz->gmt_offset_name,
                    'abbreviation' => $tz->abbreviation,
                    'tz_name' => $tz->tz_name,
                ]),
            ]
        ], 200);
    }

    /**
     * Retrieve a single state with its country info.
     */
    public function stateDetail(State $state)
    {
        $state->load('Country');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $state->id,
                'name' => $state->name,
                'state_code' => $state->state_code,
                'iso2' => $state->iso2,
                'type' => $state->type,
                'latitude' => $state->latitude,
                'longitude' => $state->longitude,
                'country' => $state->Country ? [
                    'id' => $state->Country->id,
                    'name' => $state->Country->name,
                    'iso2' => $state->Country->iso2,
                ] : null,
            ]
        ], 200);
    }

    /**
     * Retrieve a single city with state and country.
     */
    public function cityDetail(City $city)
    {
        $city->load(['State', 'Country', 'Timezone']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $city->id,
                'name' => $city->name,
                'latitude' => $city->latitude,
                'longitude' => $city->longitude,
                'type' => $city->type,
                'state' => $city->State ? [
                    'id' => $city->State->id,
                    'name' => $city->State->name,
                    'state_code' => $city->State->state_code,
                ] : null,
                'country' => $city->Country ? [
                    'id' => $city->Country->id,
                    'name' => $city->Country->name,
                    'iso2' => $city->Country->iso2,
                ] : null,
                'timezone' => $city->Timezone ? [
                    'zone_name' => $city->Timezone->zone_name,
                    'abbreviation' => $city->Timezone->abbreviation,
                    'gmt_offset_name' => $city->Timezone->gmt_offset_name,
                ] : null,
            ]
        ], 200);
    }

    /**
     * Retrieve all states belonging to a country.
     */
    public function countryStates(Country $country, Request $request)
    {
        $query = State::where('country_id', $country->id)
            ->select('id', 'name', 'state_code', 'iso2', 'type', 'latitude', 'longitude');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        $states = $query->orderBy('name')->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $states->items(),
            'meta' => [
                'country' => $country->name,
                'current_page' => $states->currentPage(),
                'last_page' => $states->lastPage(),
                'total' => $states->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve all cities belonging to a country.
     */
    public function countryCities(Country $country, Request $request)
    {
        $query = City::where('country_id', $country->id)
            ->select('id', 'name', 'state_id', 'latitude', 'longitude', 'type');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }
        if ($request->has('state_id')) {
            $query->where('state_id', $request->query('state_id'));
        }

        $cities = $query->orderBy('name')->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $cities->items(),
            'meta' => [
                'country' => $country->name,
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'total' => $cities->total(),
            ]
        ], 200);
    }

    /**
     * Retrieve all timezones belonging to a country.
     */
    public function countryTimezones(Country $country)
    {
        $timezones = Timezone::where('country_id', $country->id)->get();

        return response()->json([
            'success' => true,
            'data' => $timezones->map(fn($tz) => [
                'id' => $tz->id,
                'zone_name' => $tz->zone_name,
                'gmt_offset' => $tz->gmt_offset,
                'gmt_offset_name' => $tz->gmt_offset_name,
                'abbreviation' => $tz->abbreviation,
                'tz_name' => $tz->tz_name,
            ]),
            'meta' => [
                'country' => $country->name,
                'total' => $timezones->count(),
            ]
        ], 200);
    }

    /**
     * Retrieve all cities in a state.
     */
    public function stateCities(State $state, Request $request)
    {
        $query = City::where('state_id', $state->id)
            ->select('id', 'name', 'latitude', 'longitude', 'type');

        if ($request->has('name')) {
            $query->where('name', 'LIKE', '%' . $request->query('name') . '%');
        }

        $cities = $query->orderBy('name')->paginate($request->query('limit', 100));

        return response()->json([
            'success' => true,
            'data' => $cities->items(),
            'meta' => [
                'state' => $state->name,
                'current_page' => $cities->currentPage(),
                'last_page' => $cities->lastPage(),
                'total' => $cities->total(),
            ]
        ], 200);
    }

    /**
     * Search bank branches by name, address, or IFSC.
     */
    public function branchSearch(Request $request)
    {
        $q = $request->query('search_query', $request->query('q', $request->query('query')));
        if (!$q || strlen($q) < 2) {
            return response()->json(['success' => false, 'message' => 'Please provide a search query of at least 2 characters (e.g. ?search_query=andheri).'], 400);
        }

        $query = BankBranch::with(['bank', 'city', 'state'])
            ->where(function($qb) use ($q) {
                $qb->where('branch', 'LIKE', "%{$q}%")
                   ->orWhere('ifsc', 'LIKE', "%{$q}%")
                   ->orWhere('address', 'LIKE', "%{$q}%")
                   ->orWhere('micr', 'LIKE', "%{$q}%")
                   ->orWhereHas('bank', function($bq) use ($q) {
                       $bq->where('name', 'LIKE', "%{$q}%");
                   });
            });

        // Optional filters
        if ($request->has('state_id')) {
            $query->where('state_id', $request->query('state_id'));
        }
        if ($request->has('city_id')) {
            $query->where('city_id', $request->query('city_id'));
        }
        if ($request->has('bank_id')) {
            $query->where('bank_id', $request->query('bank_id'));
        }

        $branches = $query->paginate($request->query('limit', 50));

        return response()->json([
            'success' => true,
            'data' => $branches->items(),
            'meta' => [
                'query' => $q,
                'current_page' => $branches->currentPage(),
                'last_page' => $branches->lastPage(),
                'total' => $branches->total(),
            ]
        ], 200);
    }

    /**
     * Convert an amount from one currency to another using stored exchange rates.
     * Usage: ?from=USD&to=INR&amount=100
     */
    public function currencyConvert(Request $request)
    {
        $from = strtoupper($request->query('from', ''));
        $to = strtoupper($request->query('to', ''));
        $amount = floatval($request->query('amount', 0));

        if (!$from || !$to) {
            return response()->json(['success' => false, 'message' => 'Please provide both "from" and "to" currency codes (e.g. ?from=USD&to=INR&amount=100).'], 400);
        }
        if ($amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Please provide a positive "amount" parameter.'], 400);
        }

        // Get rates for both currencies relative to USD
        $fromRate = null;
        $toRate = null;

        if ($from === 'USD') {
            $fromRate = 1;
        } else {
            $fromConversion = CurrencyConversion::where('currency', $from)->first();
            if (!$fromConversion) {
                return response()->json(['success' => false, 'message' => "Exchange rate not found for {$from}."], 404);
            }
            $fromRate = $fromConversion->usd_conversion_rate;
        }

        if ($to === 'USD') {
            $toRate = 1;
        } else {
            $toConversion = CurrencyConversion::where('currency', $to)->first();
            if (!$toConversion) {
                return response()->json(['success' => false, 'message' => "Exchange rate not found for {$to}."], 404);
            }
            $toRate = $toConversion->usd_conversion_rate;
        }

        // Convert: from → USD → to
        // fromRate = how many USD per 1 unit of "from"
        // toRate = how many USD per 1 unit of "to"
        // So: convertedAmount = amount * (fromRate / toRate)
        $rate = $fromRate / $toRate;
        $convertedAmount = round($amount * $rate, 4);

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'amount' => $amount,
                'converted_amount' => $convertedAmount,
                'exchange_rate' => round($rate, 6),
                'provider' => 'SetuGeo Financial Engine',
            ]
        ], 200);
    }

    /**
     * Validate an Indian address by cross-checking pincode, state, and city.
     * Usage: ?pincode=400001 or ?pincode=400001&state=Maharashtra&city=Mumbai
     */
    public function addressValidate(Request $request)
    {
        $pincodeVal = $request->query('pincode');
        if (!$pincodeVal) {
            return response()->json(['success' => false, 'message' => 'Please provide a "pincode" parameter (e.g. ?pincode=400001).'], 400);
        }

        $pincodes = Pincode::with(['state', 'city', 'country'])
            ->where('postal_code', $pincodeVal)
            ->get();

        if ($pincodes->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => false,
                    'pincode' => $pincodeVal,
                    'message' => 'Pincode not found in our database.',
                ]
            ], 200);
        }

        $result = [
            'is_valid' => true,
            'pincode' => $pincodeVal,
            'matches' => [],
            'warnings' => [],
        ];

        foreach ($pincodes as $pin) {
            $result['matches'][] = [
                'state' => $pin->state ? $pin->state->name : null,
                'city' => $pin->city ? $pin->city->name : null,
                'country' => $pin->country ? $pin->country->name : null,
                'latitude' => $pin->latitude,
                'longitude' => $pin->longitude,
            ];
        }

        // Cross-check if user provided state/city
        $stateParam = $request->query('state');
        $cityParam = $request->query('city');

        if ($stateParam) {
            $stateMatches = $pincodes->filter(fn($p) => $p->state && stripos($p->state->name, $stateParam) !== false);
            if ($stateMatches->isEmpty()) {
                $result['warnings'][] = "State '{$stateParam}' does not match pincode {$pincodeVal}. Expected: " . $pincodes->pluck('state.name')->unique()->filter()->implode(', ');
                $result['is_valid'] = false;
            }
        }

        if ($cityParam) {
            $cityMatches = $pincodes->filter(fn($p) => $p->city && stripos($p->city->name, $cityParam) !== false);
            if ($cityMatches->isEmpty()) {
                $result['warnings'][] = "City '{$cityParam}' does not match pincode {$pincodeVal}. Expected: " . $pincodes->pluck('city.name')->unique()->filter()->implode(', ');
                $result['is_valid'] = false;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * Bank coverage: which states/cities a bank operates in.
     */
    public function bankCoverage(Bank $bank, Request $request)
    {
        $stateIds = BankBranch::where('bank_id', $bank->id)->pluck('state_id')->unique();
        $cityIds = BankBranch::where('bank_id', $bank->id)->pluck('city_id')->unique();

        $states = State::whereIn('id', $stateIds)->select('id', 'name', 'state_code')->orderBy('name')->get();
        $totalBranches = BankBranch::where('bank_id', $bank->id)->count();

        // Per-state branch counts
        $stateBranches = BankBranch::where('bank_id', $bank->id)
            ->selectRaw('state_id, COUNT(*) as branch_count')
            ->groupBy('state_id')
            ->pluck('branch_count', 'state_id');

        return response()->json([
            'success' => true,
            'data' => [
                'bank' => [
                    'id' => $bank->id,
                    'name' => $bank->name,
                ],
                'total_branches' => $totalBranches,
                'total_states' => $states->count(),
                'total_cities' => $cityIds->count(),
                'states' => $states->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'state_code' => $s->state_code,
                    'branches' => $stateBranches[$s->id] ?? 0,
                ]),
            ]
        ], 200);
    }

    /**
     * Compare two countries based on demographic and economic data.
     */
    public function countriesCompare(Request $request)
    {
        $c1Id = $request->query('c1_id', $request->query('c1', $request->query('country1')));
        $c2Id = $request->query('c2_id', $request->query('c2', $request->query('country2')));

        if (!$c1Id || !$c2Id) {
            return response()->json(['success' => false, 'message' => 'Please provide two country IDs to compare (e.g. ?c1_id=101&c2_id=233).'], 400);
        }

        $c1 = Country::find($c1Id);
        $c2 = Country::find($c2Id);

        if (!$c1 || !$c2) {
            return response()->json(['success' => false, 'message' => 'One or both countries not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'countries' => [$c1->name, $c2->name],
                'comparison' => [
                    'population' => ['val1' => $c1->population, 'val2' => $c2->population, 'diff' => floatval($c1->population) - floatval($c2->population)],
                    'gdp' => ['val1' => $c1->gdp, 'val2' => $c2->gdp, 'diff' => floatval($c1->gdp) - floatval($c2->gdp)],
                    'area_sq_km' => ['val1' => $c1->area_sq_km, 'val2' => $c2->area_sq_km, 'diff' => floatval($c1->area_sq_km) - floatval($c2->area_sq_km)],
                    'standard_tax_rate' => ['val1' => $c1->standard_tax_rate, 'val2' => $c2->standard_tax_rate, 'diff' => floatval($c1->standard_tax_rate) - floatval($c2->standard_tax_rate)],
                    'currencies' => ['val1' => $c1->currency, 'val2' => $c2->currency],
                    'phonecodes' => ['val1' => $c1->phonecode, 'val2' => $c2->phonecode],
                    'regions' => ['val1' => $c1->Region ? $c1->Region->name : 'N/A', 'val2' => $c2->Region ? $c2->Region->name : 'N/A'],
                ]
            ]
        ], 200);
    }

    /**
     * Find countries that are geographically nearest to the specified country.
     */
    public function countryNeighbors(Country $country, Request $request)
    {
        if (!$country->latitude || !$country->longitude) {
            return response()->json(['success' => false, 'message' => 'Coordinates not available for this country.'], 400);
        }

        $lat = $country->latitude;
        $lng = $country->longitude;
        $limit = $request->query('limit', 5);

        $rawDistance = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $neighbors = Country::where('id', '!=', $country->id)
            ->select('id', 'name', 'iso2', 'emoji')
            ->selectRaw("{$rawDistance} AS distance")
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $neighbors,
        ], 200);
    }

    /**
     * Convert time between two specific timezone identifiers.
     */
    public function timezoneConvert(Request $request)
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $timeStr = $request->query('time', now()->toTimeString());

        if (!$from || !$to) {
            return response()->json(['success' => false, 'message' => 'Please provide "from" and "to" timezone names (e.g. ?from=Asia/Kolkata&to=America/New_York).'], 400);
        }

        $now = now();
        $date = new \DateTime($timeStr, new \DateTimeZone($from));
        $fromTime = $date->format('Y-m-d H:i:s');
        $date->setTimezone(new \DateTimeZone($to));
        $toTime = $date->format('Y-m-d H:i:s');

        return response()->json([
            'success' => true,
            'data' => [
                'from' => [
                    'zone' => $from,
                    'time' => $fromTime,
                ],
                'to' => [
                    'zone' => $to,
                    'time' => $toTime,
                ]
            ]
        ], 200);
    }

    /**
     * Simple address autocomplete suggestions as user types.
     */
    public function addressAutocomplete(Request $request)
    {
        $q = $request->query('search_query', $request->query('q', $request->query('query', '')));
        if (strlen($q) < 3) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $limit = $request->query('limit', 10);

        // Search across cities, states, and countries
        $cities = City::where('name', 'LIKE', "{$q}%")->limit(5)->get(['id', 'name'])->map(fn($c) => ['type' => 'city', 'id' => $c->id, 'text' => "City: {$c->name}"]);
        $states = State::where('name', 'LIKE', "{$q}%")->limit(3)->get(['id', 'name'])->map(fn($s) => ['type' => 'state', 'id' => $s->id, 'text' => "State: {$s->name}"]);
        $countries = Country::where('name', 'LIKE', "{$q}%")->limit(2)->get(['id', 'name'])->map(fn($c) => ['type' => 'country', 'id' => $c->id, 'text' => "Country: {$c->name}"]);

        return response()->json([
            'success' => true,
            'data' => $cities->concat($states)->concat($countries)->take($limit),
        ], 200);
    }

    /**
     * GET /countries/economic-profile
     * Filter countries by economic indicators — income level, OECD membership, EU membership, GDP range.
     * Useful for market research, expansion planning, and compliance screening.
     * Query: income_level=High|Upper-middle|Lower-middle|Low, is_oecd=true, is_eu=true, region_id, sort_by=gdp|population
     */
    public function economicProfile(Request $request): JsonResponse
    {
        $query = Country::query()->select(
            'id', 'name', 'iso2', 'iso3', 'capital', 'currency', 'currency_symbol',
            'population', 'gdp', 'income_level', 'is_oecd', 'is_eu',
            'area_sq_km', 'region_id', 'subregion_id'
        );

        if ($incomeLevel = $request->income_level) $query->where('income_level', $incomeLevel);
        if ($request->boolean('is_oecd', false))   $query->where('is_oecd', true);
        if ($request->boolean('is_eu', false))      $query->where('is_eu', true);
        if ($regionId = $request->region_id)        $query->where('region_id', $regionId);
        if ($gdpMin = $request->gdp_min)            $query->where('gdp', '>=', $gdpMin);
        if ($gdpMax = $request->gdp_max)            $query->where('gdp', '<=', $gdpMax);

        $sortBy = in_array($request->sort_by, ['gdp', 'population', 'area_sq_km', 'name']) ? $request->sort_by : 'gdp';
        $query->orderBy($sortBy, 'desc');

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    /**
     * GET /countries/tax-data
     * Tax system and standard tax rates for all countries.
     * Useful for fintech, compliance, and cross-border payment applications.
     * Query: tax_system=Territorial|Worldwide, region_id
     */
    public function taxData(Request $request): JsonResponse
    {
        $query = Country::query()
            ->whereNotNull('tax_system')
            ->select('id', 'name', 'iso2', 'iso3', 'currency', 'tax_system', 'standard_tax_rate', 'income_level', 'region_id');

        if ($taxSystem = $request->tax_system) $query->where('tax_system', $taxSystem);
        if ($regionId  = $request->region_id)  $query->where('region_id', $regionId);

        $data = $query->orderBy('name')->get();

        $summary = [
            'total_countries' => $data->count(),
            'systems'         => $data->groupBy('tax_system')->map->count(),
            'avg_tax_rate'    => round($data->whereNotNull('standard_tax_rate')->avg('standard_tax_rate'), 2),
        ];

        return response()->json(['success' => true, 'summary' => $summary, 'data' => $data]);
    }

    /**
     * GET /countries/analysis/regional-gdp
     * Total and average GDP grouped by geographic region or sub-region.
     * Understand which parts of the world concentrate economic output.
     */
    public function regionalGdp(Request $request): JsonResponse
    {
        $groupBy = $request->get('group_by', 'region');

        if ($groupBy === 'subregion') {
            $data = DB::table('countries as c')
                ->join('sub_regions as sr', 'c.subregion_id', '=', 'sr.id')
                ->whereNotNull('c.gdp')
                ->groupBy('sr.id', 'sr.name')
                ->orderBy('total_gdp', 'desc')
                ->select(
                    'sr.id as subregion_id', 'sr.name as subregion',
                    DB::raw('COUNT(*) as country_count'),
                    DB::raw('ROUND(SUM(c.gdp), 2) as total_gdp'),
                    DB::raw('ROUND(AVG(c.gdp), 2) as avg_gdp'),
                    DB::raw('ROUND(SUM(c.population), 0) as total_population')
                )->get();
        } else {
            $data = DB::table('countries as c')
                ->join('regions as r', 'c.region_id', '=', 'r.id')
                ->whereNotNull('c.gdp')
                ->groupBy('r.id', 'r.name')
                ->orderBy('total_gdp', 'desc')
                ->select(
                    'r.id as region_id', 'r.name as region',
                    DB::raw('COUNT(*) as country_count'),
                    DB::raw('ROUND(SUM(c.gdp), 2) as total_gdp'),
                    DB::raw('ROUND(AVG(c.gdp), 2) as avg_gdp'),
                    DB::raw('ROUND(SUM(c.population), 0) as total_population')
                )->get();
        }

        return response()->json(['success' => true, 'group_by' => $groupBy, 'data' => $data]);
    }

    /**
     * GET /country/{country}/economic-summary
     * Full economic profile for a single country — GDP, population, tax, currency, trade info.
     */
    public function economicSummary(Country $country): JsonResponse
    {
        $data = Country::with(['region', 'subRegion'])
            ->where('id', $country->id)
            ->select(
                'id', 'name', 'iso2', 'iso3', 'capital', 'currency', 'currency_name', 'currency_symbol',
                'population', 'gdp', 'income_level', 'is_oecd', 'is_eu',
                'area_sq_km', 'tax_system', 'standard_tax_rate',
                'driving_side', 'measurement_system',
                'phonecode', 'tld', 'nationality', 'region_id', 'subregion_id'
            )
            ->first();

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * GET /banks/digital-coverage
     * Banks ranked by percentage of branches supporting digital payment methods.
     * Query: capability=upi|neft|rtgs|imps|swift — rank by a specific capability.
     */
    public function bankDigitalCoverage(Request $request): JsonResponse
    {
        $capability = strtolower($request->get('capability', 'upi'));
        $validCaps  = ['upi', 'neft', 'rtgs', 'imps', 'swift'];
        if (!in_array($capability, $validCaps)) {
            return response()->json(['success' => false, 'message' => 'Invalid capability. Valid: ' . implode(', ', $validCaps)], 422);
        }

        $data = DB::table('bank_branches as bb')
            ->join('banks as b', 'bb.bank_id', '=', 'b.id')
            ->groupBy('b.id', 'b.name')
            ->orderBy('coverage_pct', 'desc')
            ->select(
                'b.id as bank_id', 'b.name as bank_name',
                DB::raw('COUNT(*) as total_branches'),
                DB::raw("SUM(CASE WHEN bb.upi  = 1 THEN 1 ELSE 0 END) as upi_branches"),
                DB::raw("SUM(CASE WHEN bb.neft = 1 THEN 1 ELSE 0 END) as neft_branches"),
                DB::raw("SUM(CASE WHEN bb.rtgs = 1 THEN 1 ELSE 0 END) as rtgs_branches"),
                DB::raw("SUM(CASE WHEN bb.imps = 1 THEN 1 ELSE 0 END) as imps_branches"),
                DB::raw("SUM(CASE WHEN bb.swift= 1 THEN 1 ELSE 0 END) as swift_branches"),
                DB::raw("ROUND(SUM(CASE WHEN bb.{$capability} = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as coverage_pct")
            )
            ->get();

        return response()->json(['success' => true, 'ranked_by' => $capability . '_coverage_pct', 'data' => $data]);
    }

    /**
     * GET /bank/{bank}/swift-branches
     * All branches of a bank that support international wire transfers (SWIFT).
     * Useful for cross-border payment routing.
     */
    public function swiftBranches(Bank $bank, Request $request): JsonResponse
    {
        $stateId = $request->get('state_id');

        $query = BankBranch::where('bank_id', $bank->id)
            ->where('swift', true)
            ->with(['state', 'city']);

        if ($stateId) $query->where('state_id', $stateId);

        $branches = $query->select('id', 'ifsc', 'branch', 'address', 'city_id', 'state_id', 'micr', 'contact')
            ->orderBy('branch')
            ->get();

        return response()->json(['success' => true, 'bank' => $bank->name, 'swift_branch_count' => $branches->count(), 'data' => $branches]);
    }

    /**
     * GET /user/usage-breakdown
     * API credit usage grouped by endpoint category (Geo, Banking, Equity, MF, etc.)
     * Helps users understand where their credits are being consumed.
     */
    public function usageBreakdown(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = min((int)$request->get('days', 30), 90);
        $from = now()->subDays($days)->startOfDay();

        $logs = DB::table('api_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $from)
            ->where('credit_deducted', true)
            ->select('endpoint', DB::raw('COUNT(*) as calls'))
            ->groupBy('endpoint')
            ->orderBy('calls', 'desc')
            ->get();

        $categories = [
            'Mutual Funds' => '/mf/',
            'Equities'     => ['/equit', '/equity'],
            'Indices'      => '/indic',
            'Market'       => '/market',
            'Banking'      => ['/bank', '/banks'],
            'Geography'    => ['/countr', '/state', '/city', '/region', '/pincode', '/timezone'],
            'Currency'     => '/currency',
            'Geospatial'   => '/geospatial',
            'Address'      => '/address',
        ];

        $breakdown = [];
        foreach ($categories as $name => $patterns) {
            $patterns = (array)$patterns;
            $count    = $logs->filter(fn($l) => collect($patterns)->some(fn($p) => str_contains($l->endpoint, $p)))->sum('calls');
            if ($count > 0) $breakdown[] = ['category' => $name, 'calls' => $count];
        }
        usort($breakdown, fn($a, $b) => $b['calls'] - $a['calls']);

        $total = $logs->sum('calls');

        return response()->json([
            'success'    => true,
            'period_days' => $days,
            'total_calls' => $total,
            'breakdown'  => $breakdown,
        ]);
    }

    /**
     * GET /user/usage-history
     * Daily API call count for the last N days — trend view of API consumption.
     * Query: days=30 (max 90)
     */
    public function usageHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = min((int)$request->get('days', 30), 90);
        $from = now()->subDays($days)->startOfDay();

        $data = DB::table('api_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $from)
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as total_calls'),
                DB::raw('SUM(CASE WHEN credit_deducted = 1 THEN 1 ELSE 0 END) as credited_calls')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success'     => true,
            'period_days' => $days,
            'data'        => $data,
        ]);
    }
}
