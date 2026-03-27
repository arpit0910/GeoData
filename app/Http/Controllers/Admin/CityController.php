<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Timezone;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WebCitiesImport;

class CityController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = City::with(['Country', 'State', 'Timezone']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhereHas('Country', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('State', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('Timezone', function($subQ) use ($search) {
                          $subQ->where('zone_name', 'like', "%{$search}%");
                      });
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $cities = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => City::count(),
                'recordsFiltered' => $total,
                'data' => $cities
            ]);
        }

        return view('cities.index');
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $timezones = Timezone::orderBy('zone_name')->get();
        return view('cities.create', compact('countries', 'states', 'timezones'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'type' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone_id' => 'nullable|exists:timezones,id',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        City::create($request->all());

        return redirect()->route('cities.index')->with('success', 'City created successfully.');
    }

    public function edit(City $city)
    {
        $countries = Country::orderBy('name')->get();
        $states = State::orderBy('name')->get();
        $timezones = Timezone::orderBy('zone_name')->get();
        return view('cities.edit', compact('city', 'countries', 'states', 'timezones'));
    }

    public function update(Request $request, City $city)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'type' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone_id' => 'nullable|exists:timezones,id',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        $city->update($request->all());

        return redirect()->route('cities.index')->with('success', 'City updated successfully.');
    }

    public function destroy(City $city)
    {
        $city->delete();
        return redirect()->route('cities.index')->with('success', 'City deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        Excel::import(new WebCitiesImport, $request->file('import_file'));

        return redirect()->back()->with('success', 'Cities imported successfully.');
    }
}
