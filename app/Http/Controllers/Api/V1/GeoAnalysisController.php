<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Pincode;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GeoAnalysisController extends Controller
{
    /**
     * Retrieve aggregate data counts to plan data fetching and spending.
     * This endpoint is intended to be free (no credit deduction).
     */
    public function stats(Request $request)
    {
        $countryId = $request->query('country_id');
        $stateId = $request->query('state_id');
        $cityId = $request->query('city_id');

        $data = [
            'total_countries' => !$countryId ? Country::count() : 1,
        ];

        if ($countryId) {
            $data['total_states'] = State::where('country_id', $countryId)->count();
            $data['total_cities'] = City::where('country_id', $countryId)->count();
            $data['total_pincodes'] = Pincode::where('country_id', $countryId)->count();
            
            if ($stateId) {
                $data['state_cities'] = City::where('state_id', $stateId)->count();
                $data['state_pincodes'] = Pincode::where('state_id', $stateId)->count();
            }
            
            if ($cityId) {
                $data['city_pincodes'] = Pincode::where('city_id', $cityId)->count();
            }
        } else {
            $data['total_states'] = State::count();
            $data['total_cities'] = City::count();
            $data['total_pincodes'] = Pincode::count();
        }

        return response()->json([
            'success' => true,
            'message' => 'Geographical data statistics retrieved successfully.',
            'data' => $data
        ]);
    }

    /**
     * Calculate distance between two coordinate points using Haversine formula.
     * Credit-based endpoint.
     */
    public function distance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat1' => 'required|numeric|between:-90,90',
            'lng1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lng2' => 'required|numeric|between:-180,180',
            'unit' => 'nullable|string|in:km,miles'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $lat1 = deg2rad($request->lat1);
        $lng1 = deg2rad($request->lng1);
        $lat2 = deg2rad($request->lat2);
        $lng2 = deg2rad($request->lng2);

        $earthRadius = ($request->query('unit', 'km') === 'miles') ? 3959 : 6371;

        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($lngDelta / 2), 2)));
        $distance = $angle * $earthRadius;

        return response()->json([
            'success' => true,
            'data' => [
                'distance' => round($distance, 4),
                'unit' => $request->query('unit', 'km')
            ]
        ]);
    }

    /**
     * Search for nearby Cities or Pincodes within a radius.
     * Credit-based endpoint.
     */
    public function nearby(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:0.1|max:500',
            'type' => 'required|string|in:city,pincode',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius;
        $limit = $request->query('limit', 50);

        if ($request->type === 'city') {
            $query = City::select('*');
        } else {
            $query = Pincode::select('*');
        }

        // Haversine implementation in Raw SQL for database performance
        $rawDistance = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $results = $query->selectRaw("{$rawDistance} AS distance")
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}
