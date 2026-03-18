<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Region;
use App\Models\SubRegion;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Country::with(['Region', 'SubRegion']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('iso2', 'like', "%{$search}%")
                      ->orWhere('capital', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $countries = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => Country::count(),
                'recordsFiltered' => $total,
                'data' => $countries
            ]);
        }

        return view('countries.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $regions = Region::all();
        $subRegions = SubRegion::all();
        return view('countries.create', compact('regions', 'subRegions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'iso3' => 'nullable|string|max:3',
            'iso2' => 'nullable|string|max:2',
            'numeric_code' => 'nullable|string|max:3',
            'phonecode' => 'nullable|string|max:255',
            'capital' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'currency_name' => 'nullable|string|max:255',
            'currency_symbol' => 'nullable|string|max:255',
            'tld' => 'nullable|string|max:255',
            'native' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'subregion_id' => 'nullable|exists:sub_regions,id',
            'nationality' => 'nullable|string|max:255',
            'area_sq_km' => 'nullable|numeric',
            'postal_code_format' => 'nullable|string|max:255',
            'postal_code_regex' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'emoji' => 'nullable|string|max:255',
            'emojiU' => 'nullable|string|max:255',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        Country::create($validatedData);

        return redirect()->route('countries.index')->with('success', 'Country created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Not used right now
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $country = Country::findOrFail($id);
        $regions = Region::all();
        $subRegions = SubRegion::all();
        return view('countries.edit', compact('country', 'regions', 'subRegions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $country = Country::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'iso3' => 'nullable|string|max:3',
            'iso2' => 'nullable|string|max:2',
            'numeric_code' => 'nullable|string|max:3',
            'phonecode' => 'nullable|string|max:255',
            'capital' => 'nullable|string|max:255',
            'currency' => 'nullable|string|max:255',
            'currency_name' => 'nullable|string|max:255',
            'currency_symbol' => 'nullable|string|max:255',
            'tld' => 'nullable|string|max:255',
            'native' => 'nullable|string|max:255',
            'region_id' => 'nullable|integer',
            'subregion_id' => 'nullable|integer',
            'nationality' => 'nullable|string|max:255',
            'area_sq_km' => 'nullable|numeric',
            'postal_code_format' => 'nullable|string|max:255',
            'postal_code_regex' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'emoji' => 'nullable|string|max:255',
            'emojiU' => 'nullable|string|max:255',
            'wiki_data_id' => 'nullable|string|max:255',
        ]);

        $country->update($validatedData);

        return redirect()->route('countries.index')->with('success', 'Country updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $country = Country::findOrFail($id);
        $country->delete();
        return redirect()->route('countries.index')->with('success', 'Country deleted successfully.');
    }

    /**
     * Import a newly uploaded file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx'
        ]);

        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\WebCountriesImport, $request->file('import_file'));

        return redirect()->back()->with('success', 'Countries imported successfully.');
    }
}
