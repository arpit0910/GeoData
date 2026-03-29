@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit State: {{ $state->name }}</h1>
    </div>
    <a href="{{ route('states.index') }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium flex items-center bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('states.update', $state->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $state->name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter state name">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" id="country_id" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Country</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ old('country_id', $state->country_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <input type="text" name="type" id="type" value="{{ old('type', $state->type) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. province">
                    @error('type')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="iso2" class="block text-sm font-medium text-gray-700">ISO2 Code</label>
                    <input type="text" name="iso2" id="iso2" value="{{ old('iso2', $state->iso2) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. ON">
                    @error('iso2')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="iso3166_2" class="block text-sm font-medium text-gray-700">ISO3166-2</label>
                    <input type="text" name="iso3166_2" id="iso3166_2" value="{{ old('iso3166_2', $state->iso3166_2) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. CA-ON">
                    @error('iso3166_2')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="fips_code" class="block text-sm font-medium text-gray-700">FIPS Code</label>
                    <input type="text" name="fips_code" id="fips_code" value="{{ old('fips_code', $state->fips_code) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('fips_code')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $state->latitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 20.00000000">
                    @error('latitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $state->longitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 77.00000000">
                    @error('longitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="timezone_id" class="block text-sm font-medium text-gray-700">Timezone</label>
                    <select name="timezone_id" id="timezone_id" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Timezone</option>
                        @foreach(\App\Models\Timezone::orderBy('zone_name')->get() as $tz)
                            <option value="{{ $tz->id }}" {{ old('timezone_id', $state->timezone_id) == $tz->id ? 'selected' : '' }}>{{ $tz->zone_name }}</option>
                        @endforeach
                    </select>
                    @error('timezone_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="wiki_data_id" class="block text-sm font-medium text-gray-700">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id', $state->wiki_data_id) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Q1904">
                    @error('wiki_data_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="state_code" class="block text-sm font-medium text-gray-700">State/GST Code</label>
                    <input type="text" name="state_code" id="state_code" value="{{ old('state_code', $state->state_code) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 27 for Maharashtra">
                    @error('state_code')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
            </div>

            <div class="mt-8 border-t border-gray-200 pt-5">
                <div class="flex justify-end">
                    <a href="{{ route('states.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Cancel
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Update State
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
