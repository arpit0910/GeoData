<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\State;
use App\Models\Country;

class StateController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = State::with('Country','Timezone');
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('iso2', 'like', "%{$search}%")
                      ->orWhere('type', 'like', "%{$search}%")
                      ->orWhereHas('country', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $states = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => State::count(),
                'recordsFiltered' => $total,
                'data' => $states
            ]);
        }

        return view('states.index');
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();
        return view('states.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'iso2' => 'nullable|string|max:255',
            'iso3166_2' => 'nullable|string|max:255',
            'fips_code' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone_id' => 'nullable|exists:timezones,id',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        State::create($request->all());

        return redirect()->route('states.index')->with('success', 'State created successfully.');
    }

    public function edit(State $state)
    {
        $countries = Country::orderBy('name')->get();
        return view('states.edit', compact('state', 'countries'));
    }

    public function update(Request $request, State $state)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'iso2' => 'nullable|string|max:255',
            'iso3166_2' => 'nullable|string|max:255',
            'fips_code' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'timezone_id' => 'nullable|exists:timezones,id',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        $state->update($request->all());

        return redirect()->route('states.index')->with('success', 'State updated successfully.');
    }

    public function destroy(State $state)
    {
        $state->delete();
        return redirect()->route('states.index')->with('success', 'State deleted successfully.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\WebStatesImport, $request->file('import_file'));

        return redirect()->back()->with('success', 'States imported successfully.');
    }
}
