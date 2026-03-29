@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit City: {{ $city->name }}</h1>
    </div>
    <a href="{{ route('cities.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('cities.update', $city->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $city->name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter city name">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" id="country_id" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Country</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ old('country_id', $city->country_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="state_id" class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state_id" id="state_id" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a State</option>
                        @foreach($states as $s)
                            <option value="{{ $s->id }}" {{ old('state_id', $city->state_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Type</label>
                    <input type="text" name="type" id="type" value="{{ old('type', $city->type) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. city">
                    @error('type')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $city->latitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 20.00000000">
                    @error('latitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $city->longitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 77.00000000">
                    @error('longitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="timezone_id" class="block text-sm font-medium text-gray-700">Timezone</label>
                    <select name="timezone_id" id="timezone_id" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Timezone</option>
                        @foreach($timezones as $tz)
                            <option value="{{ $tz->id }}" {{ old('timezone_id', $city->timezone_id) == $tz->id ? 'selected' : '' }}>{{ $tz->zone_name }}</option>
                        @endforeach
                    </select>
                    @error('timezone_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="wiki_data_id" class="block text-sm font-medium text-gray-700">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id', $city->wiki_data_id) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Q1904">
                    @error('wiki_data_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
            </div>

                <div class="mt-10 flex justify-end items-center space-x-6">
                    <a href="{{ route('cities.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Save Changes <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
        </form>
    </div>
</div>
@endsection
