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
     * Supported units: km (default), miles, meters, centimeters.
     * Credit-based endpoint.
     */
    public function distance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat1' => 'required|numeric|between:-90,90',
            'lng1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lng2' => 'required|numeric|between:-180,180',
            'unit' => 'nullable|string|in:km,miles,meters,centimeters',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = strtolower($request->query('unit', 'km'));

        $lat1 = deg2rad($request->lat1);
        $lng1 = deg2rad($request->lng1);
        $lat2 = deg2rad($request->lat2);
        $lng2 = deg2rad($request->lng2);

        // Always compute in km using Earth's mean radius
        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;
        $angle    = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($lat1) * cos($lat2) * pow(sin($lngDelta / 2), 2)
        ));
        $distanceKm = $angle * 6371;

        // Convert to the requested unit
        $conversionFactors = [
            'km'          => 1,
            'miles'       => 0.621371,
            'meters'      => 1000,
            'centimeters' => 100000,
        ];

        $factor   = $conversionFactors[$unit] ?? 1;
        $distance = round($distanceKm * $factor, 4);

        // Human-readable unit labels for the response
        $unitLabels = [
            'km'          => 'km',
            'miles'       => 'miles',
            'meters'      => 'm',
            'centimeters' => 'cm',
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'distance'       => $distance,
                'unit'           => $unit,
                'unit_label'     => $unitLabels[$unit],
                'distance_km'    => round($distanceKm, 4),    // always included for reference
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
            ->whereRaw("{$rawDistance} <= ?", [$radius])
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * Reverse geocode a coordinate to find the nearest city and its hierarchy.
     */
    public function geocode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $lat = $request->lat;
        $lng = $request->lng;

        // Search nearest city within 50km
        $rawDistance = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $city = City::with(['State', 'Country'])
            ->select('*')
            ->selectRaw("{$rawDistance} AS distance")
            ->whereRaw("{$rawDistance} <= 50")
            ->orderBy('distance')
            ->first();

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'No known city found within 50km radius of these coordinates.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'city' => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'distance_km' => round($city->distance, 4),
                ],
                'state' => $city->State ? [
                    'id' => $city->State->id,
                    'name' => $city->State->name,
                    'state_code' => $city->State->state_code,
                ] : null,
                'country' => $city->Country ? [
                    'id' => $city->Country->id,
                    'name' => $city->Country->name,
                    'iso2' => $city->Country->iso2,
                    'emoji' => $city->Country->emoji,
                ] : null,
                'formatted_address' => "{$city->name}, " . ($city->State ? $city->State->name . ", " : "") . $city->Country->name,
            ]
        ]);
    }

    /**
     * Retrieve all locations (cities/pincodes) within a geographical bounding box.
     */
    public function boundary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min_lat' => 'required|numeric|between:-90,90',
            'max_lat' => 'required|numeric|between:-90,90',
            'min_lng' => 'required|numeric|between:-180,180',
            'max_lng' => 'required|numeric|between:-180,180',
            'type' => 'required|string|in:city,pincode',
            'limit' => 'nullable|integer|min:1|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $minLat = $request->min_lat;
        $maxLat = $request->max_lat;
        $minLng = $request->min_lng;
        $maxLng = $request->max_lng;
        $limit = $request->query('limit', 100);

        $query = $request->type === 'city' ? City::query() : Pincode::query();
        
        $results = $query->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $results,
            'meta' => [
                'count' => $results->count(),
                'type' => $request->type,
            ]
        ]);
    }

    /**
     * Simple clustering: returns representative grid-points for locations in an area.
     */
    public function cluster(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|max:500',
            'grid_size' => 'nullable|numeric|min:0.01|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $gridSize = $request->query('grid_size', 0.5);
        $radius = $request->radius;
        $lat = $request->lat;
        $lng = $request->lng;

        // Efficient grid-based clustering using floor in SQL
        $rawDistance = "(6371 * acos(cos(radians($lat)) * cos(radians(latitude)) * cos(radians(longitude) - radians($lng)) + sin(radians($lat)) * sin(radians(latitude))))";

        $clusters = City::selectRaw("
                ROUND(latitude / {$gridSize}) * {$gridSize} as grid_lat,
                ROUND(longitude / {$gridSize}) * {$gridSize} as grid_lng,
                COUNT(*) as count,
                AVG(latitude) as center_lat,
                AVG(longitude) as center_lng
            ")
            ->whereRaw("{$rawDistance} <= ?", [$radius])
            ->groupBy('grid_lat', 'grid_lng')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $clusters,
        ]);
    }
}
