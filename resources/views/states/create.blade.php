@extends('layouts.app')

@section('header', 'Add New State')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('states.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to States
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Add New State</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">Add a new state or province to the SetuGeo network.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('states.store') }}" method="POST" class="p-8 md:p-12">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div class="md:col-span-2 space-y-2">
                    <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">State Name</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. Maharashtra" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('name') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="country_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Country</label>
                    <select name="country_id" id="country_id" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Country —</option>
                        @foreach($countries as $c)
                            <option value="{{ $c->id }}" {{ old('country_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('country_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="type" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Type</label>
                    <input type="text" name="type" id="type" value="{{ old('type') }}" placeholder="e.g. state, province, territory" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('type') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="iso2" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">ISO2 Code</label>
                    <input type="text" name="iso2" id="iso2" value="{{ old('iso2') }}" placeholder="e.g. MH" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('iso2') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="iso3166_2" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">ISO3166-2</label>
                    <input type="text" name="iso3166_2" id="iso3166_2" value="{{ old('iso3166_2') }}" placeholder="e.g. IN-MH" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('iso3166_2') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="fips_code" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">FIPS Code</label>
                    <input type="text" name="fips_code" id="fips_code" value="{{ old('fips_code') }}" placeholder="e.g. IN16" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('fips_code') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="latitude" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude') }}" placeholder="e.g. 19.75470000" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('latitude') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="longitude" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude') }}" placeholder="e.g. 75.71390000" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('longitude') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="timezone_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Timezone</label>
                    <select name="timezone_id" id="timezone_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer disabled:opacity-50" disabled>
                        <option value="">— Select Country First —</option>
                    </select>
                    @error('timezone_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="wiki_data_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id') }}" placeholder="e.g. Q1191" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('wiki_data_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="state_code" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">State / GST Code</label>
                    <input type="text" name="state_code" id="state_code" value="{{ old('state_code') }}" placeholder="e.g. 27 for Maharashtra" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('state_code') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-12 flex flex-col md:flex-row justify-end items-center gap-4">
                <a href="{{ route('states.index') }}" class="w-full md:w-auto px-8 py-3.5 text-sm font-black text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-10 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Save State <i class="fas fa-save ml-3 text-sm opacity-80"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const countrySelect = $('#country_id');
        const timezoneSelect = $('#timezone_id');

        countrySelect.on('change', function() {
            const countryId = $(this).val();
            if (!countryId) return;
            timezoneSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            $.get(`/countries/${countryId}/timezones`, function(data) {
                let html = '<option value="">— Select Timezone —</option>';
                if (data.length > 0) {
                    data.forEach(function(tz) { html += `<option value="${tz.id}">${tz.zone_name}</option>`; });
                    timezoneSelect.prop('disabled', false);
                } else {
                    html = '<option value="">— No Timezones Found —</option>';
                }
                timezoneSelect.html(html);
            }).fail(function() { timezoneSelect.html('<option value="">— Error Loading —</option>').prop('disabled', false); });
        });
        if (countrySelect.val()) { countrySelect.trigger('change'); }
    });
</script>
@endpush
