@extends('layouts.app')

@section('header')
    Edit Timezone
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Timezone</h1>
    </div>
    <a href="{{ route('timezones.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('timezones.update', $timezone->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                    <select name="country_id" id="country_id" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" disabled>Select a Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ (old('country_id', $timezone->country_id) == $country->id) ? 'selected' : '' }}>{{ $country->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="zone_name" class="block text-sm font-medium text-gray-700">Zone Name</label>
                    <input type="text" name="zone_name" id="zone_name" value="{{ old('zone_name', $timezone->zone_name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('zone_name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="gmt_offset" class="block text-sm font-medium text-gray-700">GMT Offset (seconds)</label>
                    <input type="text" name="gmt_offset" id="gmt_offset" value="{{ old('gmt_offset', $timezone->gmt_offset) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('gmt_offset')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="gmt_offset_name" class="block text-sm font-medium text-gray-700">GMT Offset Name</label>
                    <input type="text" name="gmt_offset_name" id="gmt_offset_name" value="{{ old('gmt_offset_name', $timezone->gmt_offset_name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('gmt_offset_name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="abbreviation" class="block text-sm font-medium text-gray-700">Abbreviation</label>
                    <input type="text" name="abbreviation" id="abbreviation" value="{{ old('abbreviation', $timezone->abbreviation) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('abbreviation')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="tz_name" class="block text-sm font-medium text-gray-700">Timezone Name</label>
                    <input type="text" name="tz_name" id="tz_name" value="{{ old('tz_name', $timezone->tz_name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('tz_name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>

                <div class="mt-10 flex justify-end items-center space-x-6">
                    <a href="{{ route('timezones.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
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
