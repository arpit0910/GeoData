<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $regions = \App\Models\Region::paginate(50);
        return view('regions.index', compact('regions'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('regions.create');
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
            'wikiDataId' => 'nullable|string|max:255',
        ]);

        \App\Models\Region::create($validatedData);

        return redirect()->route('regions.index')->with('success', 'Region created successfully.');
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
        $region = \App\Models\Region::findOrFail($id);
        return view('regions.edit', compact('region'));
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
        $region = \App\Models\Region::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'wikiDataId' => 'nullable|string|max:255',
        ]);

        $region->update($validatedData);

        return redirect()->route('regions.index')->with('success', 'Region updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $region = \App\Models\Region::findOrFail($id);
        $region->delete();
        return redirect()->route('regions.index')->with('success', 'Region deleted successfully.');
    }
}
