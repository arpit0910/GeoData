<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubRegionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = \App\Models\SubRegion::with('region');

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $subRegions = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => \App\Models\SubRegion::count(),
                'recordsFiltered' => $total,
                'data' => $subRegions
            ]);
        }
        
        return view('subregions.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $regions = \App\Models\Region::all();
        return view('subregions.create', compact('regions'));
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
            'region_id' => 'nullable|exists:regions,id',
            'wikiDataId' => 'nullable|string|max:255',
        ]);

        \App\Models\SubRegion::create($validatedData);

        return redirect()->route('subregions.index')->with('success', 'SubRegion created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subRegion = \App\Models\SubRegion::findOrFail($id);
        $regions = \App\Models\Region::all();
        return view('subregions.edit', compact('subRegion', 'regions'));
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
        $subRegion = \App\Models\SubRegion::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'wikiDataId' => 'nullable|string|max:255',
        ]);

        $subRegion->update($validatedData);

        return redirect()->route('subregions.index')->with('success', 'SubRegion updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subRegion = \App\Models\SubRegion::findOrFail($id);
        $subRegion->delete();
        return redirect()->route('subregions.index')->with('success', 'SubRegion deleted successfully.');
    }
}
