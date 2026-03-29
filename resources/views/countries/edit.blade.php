@extends('layouts.app')

@section('header')
    Edit Country: {{ $country->name }}
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Country: {{ $country->name }}</h1>
    </div>
    <a href="{{ route('countries.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('countries.update', $country->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $country->name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter country name">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Codes Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Codes</h3>
                </div>

                <div>
                    <label for="iso2" class="block text-sm font-medium text-gray-700">ISO2 Code</label>
                    <input type="text" name="iso2" id="iso2" maxlength="2" value="{{ old('iso2', $country->iso2) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. IN">
                    @error('iso2')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="iso3" class="block text-sm font-medium text-gray-700">ISO3 Code</label>
                    <input type="text" name="iso3" id="iso3" maxlength="3" value="{{ old('iso3', $country->iso3) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. IND">
                    @error('iso3')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="numeric_code" class="block text-sm font-medium text-gray-700">Numeric Code</label>
                    <input type="text" name="numeric_code" id="numeric_code" maxlength="3" value="{{ old('numeric_code', $country->numeric_code) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 356">
                    @error('numeric_code')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="phonecode" class="block text-sm font-medium text-gray-700">Phone Code</label>
                    <input type="text" name="phonecode" id="phonecode" value="{{ old('phonecode', $country->phonecode) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 91">
                    @error('phonecode')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="tld" class="block text-sm font-medium text-gray-700">Top Level Domain (TLD)</label>
                    <input type="text" name="tld" id="tld" value="{{ old('tld', $country->tld) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. .in">
                    @error('tld')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Geography Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Geography</h3>
                </div>

                <div>
                    <label for="capital" class="block text-sm font-medium text-gray-700">Capital</label>
                    <input type="text" name="capital" id="capital" value="{{ old('capital', $country->capital) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. New Delhi">
                    @error('capital')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="native" class="block text-sm font-medium text-gray-700">Native Name</label>
                    <input type="text" name="native" id="native" value="{{ old('native', $country->native) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Bharat">
                    @error('native')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="nationality" class="block text-sm font-medium text-gray-700">Nationality</label>
                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $country->nationality) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Indian">
                    @error('nationality')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                    <select name="region_id" id="region_id" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Region</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id', $country->region_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="subregion_id" class="block text-sm font-medium text-gray-700">Subregion Select</label>
                    <select name="subregion_id" id="subregion_id" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" selected disabled>Select a Sub Region</option>
                        @foreach($subRegions as $sr)
                            <option value="{{ $sr->id }}" data-region-id="{{ $sr->region_id }}" {{ old('subregion_id', $country->subregion_id) == $sr->id ? 'selected' : '' }}>{{ $sr->name }}</option>
                        @endforeach
                    </select>
                    @error('subregion_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $country->latitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 20.00000000">
                    @error('latitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $country->longitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 77.00000000">
                    @error('longitude')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
                <div>
                    <label for="area_sq_km" class="block text-sm font-medium text-gray-700">Area (sq km)</label>
                    <input type="number" step="any" name="area_sq_km" id="area_sq_km" value="{{ old('area_sq_km', $country->area_sq_km) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 3287263.00">
                    @error('area_sq_km')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Currency Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Currency</h3>
                </div>

                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700">Currency Code</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', $country->currency) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. INR">
                    @error('currency')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="currency_name" class="block text-sm font-medium text-gray-700">Currency Name</label>
                    <input type="text" name="currency_name" id="currency_name" value="{{ old('currency_name', $country->currency_name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Indian Rupee">
                    @error('currency_name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="currency_symbol" class="block text-sm font-medium text-gray-700">Currency Symbol</label>
                    <input type="text" name="currency_symbol" id="currency_symbol" value="{{ old('currency_symbol', $country->currency_symbol) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. ₹">
                    @error('currency_symbol')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Others Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Other & Misc</h3>
                </div>

                <div>
                    <label for="emoji" class="block text-sm font-medium text-gray-700">Emoji Flag</label>
                    <input type="text" name="emoji" id="emoji" value="{{ old('emoji', $country->emoji) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 🇮🇳">
                    @error('emoji')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="emojiU" class="block text-sm font-medium text-gray-700">Emoji Code (U+)</label>
                    <input type="text" name="emojiU" id="emojiU" value="{{ old('emojiU', $country->emojiU) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. U+1F1EE U+1F1F3">
                    @error('emojiU')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="postal_code_format" class="block text-sm font-medium text-gray-700">Postal Code Format</label>
                    <input type="text" name="postal_code_format" id="postal_code_format" value="{{ old('postal_code_format', $country->postal_code_format) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. ######">
                    @error('postal_code_format')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="postal_code_regex" class="block text-sm font-medium text-gray-700">Postal Code Regex</label>
                    <input type="text" name="postal_code_regex" id="postal_code_regex" value="{{ old('postal_code_regex', $country->postal_code_regex) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. ^\d{6}$">
                    @error('postal_code_regex')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="wiki_data_id" class="block text-sm font-medium text-gray-700">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id', $country->wiki_data_id) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Q668">
                    @error('wiki_data_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Additional Info Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Additional Info</h3>
                </div>

                <div>
                    <label for="population" class="block text-sm font-medium text-gray-700">Population</label>
                    <input type="number" step="any" name="population" id="population" value="{{ old('population', $country->population) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter population">
                    @error('population')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="gdp" class="block text-sm font-medium text-gray-700">GDP</label>
                    <input type="number" step="any" name="gdp" id="gdp" value="{{ old('gdp', $country->gdp) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter GDP">
                    @error('gdp')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="max_mobile_digits" class="block text-sm font-medium text-gray-700">Max Mobile Digits</label>
                    <input type="number" name="max_mobile_digits" id="max_mobile_digits" value="{{ old('max_mobile_digits', $country->max_mobile_digits) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 10">
                    @error('max_mobile_digits')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="international_prefix" class="block text-sm font-medium text-gray-700">International Prefix</label>
                    <input type="text" name="international_prefix" id="international_prefix" value="{{ old('international_prefix', $country->international_prefix) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 00">
                    @error('international_prefix')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="trunk_prefix" class="block text-sm font-medium text-gray-700">Trunk Prefix</label>
                    <input type="text" name="trunk_prefix" id="trunk_prefix" value="{{ old('trunk_prefix', $country->trunk_prefix) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 0">
                    @error('trunk_prefix')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="income_level" class="block text-sm font-medium text-gray-700">Income Level</label>
                    <input type="text" name="income_level" id="income_level" value="{{ old('income_level', $country->income_level) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. High income">
                    @error('income_level')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="driving_side" class="block text-sm font-medium text-gray-700">Driving Side</label>
                    <select name="driving_side" id="driving_side" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="" {{ old('driving_side', $country->driving_side) == '' ? 'selected' : '' }}>Select Driving Side</option>
                        <option value="Right" {{ old('driving_side', $country->driving_side) == 'Right' ? 'selected' : '' }}>Right</option>
                        <option value="Left" {{ old('driving_side', $country->driving_side) == 'Left' ? 'selected' : '' }}>Left</option>
                    </select>
                    @error('driving_side')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="measurement_system" class="block text-sm font-medium text-gray-700">Measurement System</label>
                    <input type="text" name="measurement_system" id="measurement_system" value="{{ old('measurement_system', $country->measurement_system) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Metric">
                    @error('measurement_system')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="tax_system" class="block text-sm font-medium text-gray-700">Tax System</label>
                    <input type="text" name="tax_system" id="tax_system" value="{{ old('tax_system', $country->tax_system) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. VAT">
                    @error('tax_system')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="standard_tax_rate" class="block text-sm font-medium text-gray-700">Standard Tax Rate</label>
                    <input type="text" name="standard_tax_rate" id="standard_tax_rate" value="{{ old('standard_tax_rate', $country->standard_tax_rate) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. 10%">
                    @error('standard_tax_rate')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label for="is_oecd" class="block text-sm font-medium text-gray-700">Is OECD Member?</label>
                    <select name="is_oecd" id="is_oecd" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="0" {{ old('is_oecd', $country->is_oecd) == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_oecd', $country->is_oecd) == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>

                <div>
                    <label for="is_eu" class="block text-sm font-medium text-gray-700">Is EU Member?</label>
                    <select name="is_eu" id="is_eu" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        <option value="0" {{ old('is_eu', $country->is_eu) == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_eu', $country->is_eu) == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>

                <div class="col-span-1 lg:col-span-3">
                    <label for="timezones" class="block text-sm font-medium text-gray-700">Timezones (JSON)</label>
                    <textarea name="timezones" id="timezones" rows="3" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter timezone data in JSON format">{{ old('timezones', $country->timezones) }}</textarea>
                    @error('timezones')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
                
            </div>

                <div class="mt-10 flex justify-end items-center space-x-6">
                    <a href="{{ route('countries.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const regionSelect = document.getElementById('region_id');
        const subregionSelect = document.getElementById('subregion_id');
        const allSubregionOptions = Array.from(subregionSelect.options).slice(1); // skip default

        function filterSubregions() {
            const selectedRegionId = regionSelect.value;
            
            // Hide all subregions
            allSubregionOptions.forEach(option => {
                option.style.display = 'none';
                option.disabled = true;
            });

            if (selectedRegionId) {
                // Show matching subregions
                allSubregionOptions.forEach(option => {
                    if (option.getAttribute('data-region-id') === selectedRegionId) {
                        option.style.display = '';
                        option.disabled = false;
                    }
                });
            }
            // Optional: reset subregion selection if filtered out and not the initial load
            if(subregionSelect.selectedOptions[0] && subregionSelect.selectedOptions[0].style.display === 'none') {
                subregionSelect.value = '';
            }
        }

        regionSelect.addEventListener('change', filterSubregions);
        // Run once on load to establish the correct state.
        filterSubregions();
    });
</script>
@endpush
