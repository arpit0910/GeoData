<?php

namespace App\Http\Controllers;

use App\Models\Pincode;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;

class PincodeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Pincode::with(['country', 'state', 'city']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('postal_code', 'like', "%{$search}%")
                      ->orWhereHas('country', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('state', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('city', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $total = Pincode::count();
            $filtered = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $pincodes = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $pincodes
            ]);
        }

        return view('pincodes.index');
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        return view('pincodes.create', compact('countries', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'postal_code' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'short_state' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'short_county' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'short_community' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|string|max:255',
        ]);

        Pincode::create($request->all());

        return redirect()->route('pincodes.index')->with('success', 'Pincode created successfully.');
    }

    public function edit(Pincode $pincode)
    {
        $countries = Country::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        return view('pincodes.edit', compact('pincode', 'countries', 'states', 'cities'));
    }

    public function update(Request $request, Pincode $pincode)
    {
        $request->validate([
            'postal_code' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'short_state' => 'nullable|string|max:255',
            'county' => 'nullable|string|max:255',
            'short_county' => 'nullable|string|max:255',
            'community' => 'nullable|string|max:255',
            'short_community' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy' => 'nullable|string|max:255',
        ]);

        $pincode->update($request->all());

        return redirect()->route('pincodes.index')->with('success', 'Pincode updated successfully.');
    }

    public function destroy(Pincode $pincode)
    {
        $pincode->delete();
        return redirect()->route('pincodes.index')->with('success', 'Pincode deleted successfully.');
    }

    public function uploadChunk(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'chunkIndex' => 'required|numeric',
            'totalChunks' => 'required|numeric',
            'fileName' => 'required|string',
        ]);

        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $request->input('fileName'));

        $tmpDir = storage_path('app/public/tmp_imports');
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $filePath = $tmpDir . '/' . md5(session()->getId() . $fileName) . '_' . $fileName;

        if ($chunkIndex == 0 && file_exists($filePath)) {
            unlink($filePath);
        }

        $chunkData = file_get_contents($request->file('file')->getRealPath());
        file_put_contents($filePath, $chunkData, FILE_APPEND);

        if ($chunkIndex == $totalChunks - 1) {
            $result = $this->processLargeCsv($filePath);
            unlink($filePath);
            return response()->json(['status' => 'success', 'message' => 'Processed ' . $result . ' records successfully!', 'recordsProcessed' => $result]);
        }

        return response()->json(['status' => 'chunk_uploaded', 'progress' => round((($chunkIndex + 1) / $totalChunks) * 100)]);
    }

    protected function processLargeCsv($path)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $countries = Country::pluck('id', 'iso2')->toArray();
        $states = [];
        foreach (State::select('id', 'name', 'country_id')->get() as $state) {
            $states[$state->country_id . '_' . $state->name] = $state->id;
        }
        $cities = [];
        foreach (City::select('id', 'name', 'state_id')->get() as $city) {
            $cities[$city->state_id . '_' . $city->name] = $city->id;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if ($header) {
            $header = array_map('strtolower', $header);
            $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        }

        $chunkSize = 1000;
        $batch = [];
        $recordsProcessed = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($header) !== count($row)) continue;
            
            $rowData = array_combine($header, $row);

            $countryCode = $rowData['country'] ?? null;
            if (!$countryCode || !isset($countries[$countryCode])) {
                continue;
            }
            $countryId = $countries[$countryCode];

            $stateName = $rowData['state'] ?? null;
            $stateKey = $countryId . '_' . $stateName;
            $stateId = $stateName && isset($states[$stateKey]) ? $states[$stateKey] : null;

            $cityName = $rowData['city'] ?? null;
            $cityKey = $stateId . '_' . $cityName;
            $cityId = $cityName && isset($cities[$cityKey]) ? $cities[$cityKey] : null;

            if (empty($rowData['postal_code'])) {
                continue;
            }

            $batch[] = [
                'postal_code'     => $rowData['postal_code'],
                'country_id'      => $countryId,
                'state_id'        => $stateId,
                'city_id'         => $cityId,
                'short_state'     => $rowData['short_state'] ?? null,
                'county'          => $rowData['county'] ?? null,
                'short_county'    => $rowData['short_county'] ?? null,
                'community'       => $rowData['community'] ?? null,
                'short_community' => $rowData['short_community'] ?? null,
                'latitude'        => $rowData['latitude'] ?? null,
                'longitude'       => $rowData['longitude'] ?? null,
                'accuracy'        => $rowData['accuracy'] ?? null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            if (count($batch) >= $chunkSize) {
                \Illuminate\Support\Facades\DB::table('pincodes')->upsert(
                    $batch,
                    ['postal_code', 'country_id'],
                    ['state_id', 'city_id', 'short_state', 'county', 'short_county', 'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
                );
                $recordsProcessed += count($batch);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            \Illuminate\Support\Facades\DB::table('pincodes')->upsert(
                $batch,
                ['postal_code', 'country_id'],
                ['state_id', 'city_id', 'short_state', 'county', 'short_county', 'community', 'short_community', 'latitude', 'longitude', 'accuracy', 'updated_at']
            );
            $recordsProcessed += count($batch);
        }

        fclose($handle);
        return $recordsProcessed;
    }

    public function lookup($postal_code)
    {
        $pincodes = Pincode::with(['country', 'state', 'city'])
            ->where('postal_code', $postal_code)
            ->get();

        if ($pincodes->isNotEmpty()) {
            $pincode = $pincodes->firstWhere('city_id', '!=', null) ?? $pincodes->first();
            
            if (!$pincode->state_id) {
                $pincodeState = $pincodes->firstWhere('state_id', '!=', null);
                if ($pincodeState) {
                    $pincode->state_id = $pincodeState->state_id;
                    $pincode->state = $pincodeState->state;
                }
            }

            if (!$pincode->city_id && ($pincode->county || $pincode->community)) {
                $cityName = $pincode->county ?: $pincode->community;
                $cityQuery = \App\Models\City::where('name', 'like', "%{$cityName}%");
                if ($pincode->state_id) {
                    $cityQuery->where('state_id', $pincode->state_id);
                }
                $cityFallback = $cityQuery->first();
                if ($cityFallback) {
                    $pincode->city_id = $cityFallback->id;
                    // Mock the relation object for the response array below
                    $pincode->setRelation('city', $cityFallback);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'country_id' => $pincode->country_id,
                    'state_id' => $pincode->state_id,
                    'state_name' => $pincode->state ? $pincode->state->name : null,
                    'city_id' => $pincode->city_id,
                    'city_name' => $pincode->city ? $pincode->city->name : null,
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Pincode not found'], 404);
    }
}
