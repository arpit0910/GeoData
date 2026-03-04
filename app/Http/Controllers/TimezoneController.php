<?php

namespace App\Http\Controllers;

use App\Models\Timezone;
use Illuminate\Http\Request;

class TimezoneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Timezone::with('country');

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('zone_name', 'like', "%{$search}%")
                      ->orWhere('tz_name', 'like', "%{$search}%")
                      ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            }

            $total = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $timezones = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => Timezone::count(),
                'recordsFiltered' => $total,
                'data' => $timezones
            ]);
        }

        return view('timezones.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $countries = \App\Models\Country::all();
        return view('timezones.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'country_id'      => 'required|exists:countries,id',
            'zone_name'       => 'nullable|string|max:255',
            'gmt_offset'      => 'nullable|string|max:255',
            'gmt_offset_name' => 'nullable|string|max:255',
            'abbreviation'    => 'nullable|string|max:255',
            'tz_name'         => 'nullable|string|max:255',
        ]);

        Timezone::create($validated);

        return redirect()->route('timezones.index')->with('success', 'Timezone created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Timezone  $timezone
     * @return \Illuminate\Http\Response
     */
    public function show(Timezone $timezone)
    {
        return view('timezones.show', compact('timezone'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Timezone  $timezone
     * @return \Illuminate\Http\Response
     */
    public function edit(Timezone $timezone)
    {
        $countries = \App\Models\Country::all();
        return view('timezones.edit', compact('timezone', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Timezone  $timezone
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Timezone $timezone)
    {
        $validated = $request->validate([
            'country_id'      => 'required|exists:countries,id',
            'zone_name'       => 'nullable|string|max:255',
            'gmt_offset'      => 'nullable|string|max:255',
            'gmt_offset_name' => 'nullable|string|max:255',
            'abbreviation'    => 'nullable|string|max:255',
            'tz_name'         => 'nullable|string|max:255',
        ]);

        $timezone->update($validated);

        return redirect()->route('timezones.index')->with('success', 'Timezone updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Timezone  $timezone
     * @return \Illuminate\Http\Response
     */
    public function destroy(Timezone $timezone)
    {
        $timezone->delete();

        return redirect()->route('timezones.index')->with('success', 'Timezone deleted successfully.');
    }
}
