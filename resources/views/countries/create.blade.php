@extends('layouts.app')

@section('header', 'Add New Country')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('countries.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Countries
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Add New Country</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">Add a new country to the SetuGeo global network.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('countries.store') }}" method="POST" class="p-8 md:p-12">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                {{-- Name --}}
                <div class="md:col-span-2 space-y-2">
                    <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Country Name</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. India" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('name') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Codes Section --}}
                <div class="md:col-span-2 pt-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Codes & Identifiers</p>
                </div>

                <div class="space-y-2">
                    <label for="iso2" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">ISO2 Code</label>
                    <input type="text" name="iso2" id="iso2" maxlength="2" value="{{ old('iso2') }}" placeholder="e.g. IN" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('iso2') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="iso3" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">ISO3 Code</label>
                    <input type="text" name="iso3" id="iso3" maxlength="3" value="{{ old('iso3') }}" placeholder="e.g. IND" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('iso3') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="numeric_code" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Numeric Code</label>
                    <input type="text" name="numeric_code" id="numeric_code" maxlength="3" value="{{ old('numeric_code') }}" placeholder="e.g. 356" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('numeric_code') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="phonecode" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Phone Code</label>
                    <input type="text" name="phonecode" id="phonecode" value="{{ old('phonecode') }}" placeholder="e.g. 91" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('phonecode') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="tld" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Top Level Domain</label>
                    <input type="text" name="tld" id="tld" value="{{ old('tld') }}" placeholder="e.g. .in" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('tld') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Geography Section --}}
                <div class="md:col-span-2 pt-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Geography</p>
                </div>

                <div class="space-y-2">
                    <label for="capital" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Capital</label>
                    <input type="text" name="capital" id="capital" value="{{ old('capital') }}" placeholder="e.g. New Delhi" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('capital') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="native" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Native Name</label>
                    <input type="text" name="native" id="native" value="{{ old('native') }}" placeholder="e.g. Bharat" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('native') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="nationality" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Nationality</label>
                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality') }}" placeholder="e.g. Indian" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('nationality') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="region_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Region</label>
                    <select name="region_id" id="region_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Region —</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="subregion_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Sub Region</label>
                    <select name="subregion_id" id="subregion_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Sub Region —</option>
                        @foreach($subRegions as $sr)
                            <option value="{{ $sr->id }}" data-region-id="{{ $sr->region_id }}" {{ old('subregion_id') == $sr->id ? 'selected' : '' }}>{{ $sr->name }}</option>
                        @endforeach
                    </select>
                    @error('subregion_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="latitude" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude') }}" placeholder="e.g. 20.00000000" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('latitude') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="longitude" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude') }}" placeholder="e.g. 77.00000000" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('longitude') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="area_sq_km" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Area (sq km)</label>
                    <input type="number" step="any" name="area_sq_km" id="area_sq_km" value="{{ old('area_sq_km') }}" placeholder="e.g. 3287263.00" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('area_sq_km') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Currency Section --}}
                <div class="md:col-span-2 pt-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Currency</p>
                </div>

                <div class="space-y-2">
                    <label for="currency" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Currency Code</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency') }}" placeholder="e.g. INR" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('currency') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="currency_name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Currency Name</label>
                    <input type="text" name="currency_name" id="currency_name" value="{{ old('currency_name') }}" placeholder="e.g. Indian Rupee" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('currency_name') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="currency_symbol" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Currency Symbol</label>
                    <input type="text" name="currency_symbol" id="currency_symbol" value="{{ old('currency_symbol') }}" placeholder="e.g. ₹" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('currency_symbol') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Other Section --}}
                <div class="md:col-span-2 pt-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Other & Misc</p>
                </div>

                <div class="space-y-2">
                    <label for="emoji" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Emoji Flag</label>
                    <input type="text" name="emoji" id="emoji" value="{{ old('emoji') }}" placeholder="e.g. 🇮🇳" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('emoji') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="emojiU" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Emoji Code (U+)</label>
                    <input type="text" name="emojiU" id="emojiU" value="{{ old('emojiU') }}" placeholder="e.g. U+1F1EE U+1F1F3" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('emojiU') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="postal_code_format" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Postal Code Format</label>
                    <input type="text" name="postal_code_format" id="postal_code_format" value="{{ old('postal_code_format') }}" placeholder="e.g. ######" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('postal_code_format') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="postal_code_regex" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Postal Code Regex</label>
                    <input type="text" name="postal_code_regex" id="postal_code_regex" value="{{ old('postal_code_regex') }}" placeholder="e.g. ^\d{6}$" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('postal_code_regex') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="wiki_data_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id') }}" placeholder="e.g. Q668" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('wiki_data_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Additional Info Section --}}
                <div class="md:col-span-2 pt-2">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Additional Info</p>
                </div>

                <div class="space-y-2">
                    <label for="population" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Population</label>
                    <input type="number" step="any" name="population" id="population" value="{{ old('population') }}" placeholder="e.g. 1380004385" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('population') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="gdp" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">GDP</label>
                    <input type="number" step="any" name="gdp" id="gdp" value="{{ old('gdp') }}" placeholder="e.g. 2875142000000" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('gdp') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="max_mobile_digits" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Max Mobile Digits</label>
                    <input type="number" name="max_mobile_digits" id="max_mobile_digits" value="{{ old('max_mobile_digits') }}" placeholder="e.g. 10" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('max_mobile_digits') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="international_prefix" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">International Prefix</label>
                    <input type="text" name="international_prefix" id="international_prefix" value="{{ old('international_prefix') }}" placeholder="e.g. 00" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('international_prefix') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="trunk_prefix" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Trunk Prefix</label>
                    <input type="text" name="trunk_prefix" id="trunk_prefix" value="{{ old('trunk_prefix') }}" placeholder="e.g. 0" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('trunk_prefix') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="income_level" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Income Level</label>
                    <input type="text" name="income_level" id="income_level" value="{{ old('income_level') }}" placeholder="e.g. Lower middle income" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('income_level') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="driving_side" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Driving Side</label>
                    <select name="driving_side" id="driving_side" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select —</option>
                        <option value="Right" {{ old('driving_side') == 'Right' ? 'selected' : '' }}>Right</option>
                        <option value="Left" {{ old('driving_side') == 'Left' ? 'selected' : '' }}>Left</option>
                    </select>
                    @error('driving_side') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="measurement_system" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Measurement System</label>
                    <input type="text" name="measurement_system" id="measurement_system" value="{{ old('measurement_system') }}" placeholder="e.g. Metric" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('measurement_system') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="tax_system" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Tax System</label>
                    <input type="text" name="tax_system" id="tax_system" value="{{ old('tax_system') }}" placeholder="e.g. GST" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('tax_system') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="standard_tax_rate" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Standard Tax Rate</label>
                    <input type="text" name="standard_tax_rate" id="standard_tax_rate" value="{{ old('standard_tax_rate') }}" placeholder="e.g. 18%" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('standard_tax_rate') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="is_oecd" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">OECD Member</label>
                    <select name="is_oecd" id="is_oecd" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('is_oecd', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_oecd', '0') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="is_eu" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">EU Member</label>
                    <select name="is_eu" id="is_eu" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="0" {{ old('is_eu', '0') == '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('is_eu', '0') == '1' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="timezones" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Timezones (JSON)</label>
                    <textarea name="timezones" id="timezones" rows="3" placeholder='e.g. [{"zone_name":"Asia/Kolkata","gmt_offset":19800}]' class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">{{ old('timezones') }}</textarea>
                    @error('timezones') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-12 flex flex-col md:flex-row justify-end items-center gap-4">
                <a href="{{ route('countries.index') }}" class="w-full md:w-auto px-8 py-3.5 text-sm font-black text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-10 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Save Country <i class="fas fa-save ml-3 text-sm opacity-80"></i>
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
        const allSubregionOptions = Array.from(subregionSelect.options).slice(1);

        function filterSubregions() {
            const selectedRegionId = regionSelect.value;
            allSubregionOptions.forEach(option => {
                option.style.display = 'none';
                option.disabled = true;
            });
            if (selectedRegionId) {
                allSubregionOptions.forEach(option => {
                    if (option.getAttribute('data-region-id') === selectedRegionId) {
                        option.style.display = '';
                        option.disabled = false;
                    }
                });
            }
            if(subregionSelect.selectedOptions[0] && subregionSelect.selectedOptions[0].style.display === 'none') {
                subregionSelect.value = '';
            }
        }

        regionSelect.addEventListener('change', filterSubregions);
        filterSubregions();
    });
</script>
@endpush
