@extends('layouts.app')

@section('content')
{{-- Page Header --}}
<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Edit Pincode</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">
            Updating: <span class="font-black text-amber-600 dark:text-amber-500">{{ $pincode->postal_code }}</span>
        </p>
    </div>
    <a href="{{ route('pincodes.index') }}" class="inline-flex items-center text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i> Back to Pincodes
    </a>
</div>

{{-- Error Alert --}}
@if ($errors->any())
    <div class="mb-6 flex items-start gap-3 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 rounded-2xl px-5 py-4">
        <i class="fas fa-exclamation-circle mt-0.5 shrink-0"></i>
        <ul class="text-sm list-disc list-inside space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('pincodes.update', $pincode) }}" method="POST">
    @csrf
    @method('PUT')

    {{-- Section: Core Identity --}}
    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-500/10 flex items-center justify-center">
                <i class="fas fa-map-pin text-amber-600 dark:text-amber-500 text-sm"></i>
            </span>
            <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Location Details</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- Postal Code --}}
            <div class="md:col-span-2">
                <label for="postal_code" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Postal Code <span class="text-red-500">*</span>
                </label>
                <input type="text" name="postal_code" id="postal_code"
                    value="{{ old('postal_code', $pincode->postal_code) }}" required
                    placeholder="e.g. 400001"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            {{-- Country --}}
            <div>
                <label for="country_id" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Country <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <select name="country_id" id="country_id" required
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Country —</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id', $pincode->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                </div>
            </div>

            {{-- State — pre-populated from controller --}}
            <div>
                <label for="state_id" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">State</label>
                <div class="relative">
                    <select name="state_id" id="state_id"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer"
                        @if($states->isEmpty()) disabled @endif>
                        <option value="">{{ $states->isEmpty() ? '— Select Country First —' : '— Select State —' }}</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ (int)old('state_id', $pincode->state_id) === (int)$state->id ? 'selected' : '' }}>
                                {{ $state->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    <span id="state_loader" class="hidden absolute right-9 top-1/2 -translate-y-1/2">
                        <i class="fas fa-circle-notch fa-spin text-amber-500 text-xs"></i>
                    </span>
                </div>
            </div>

            {{-- City — pre-populated from controller --}}
            <div>
                <label for="city_id" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">City</label>
                <div class="relative">
                    <select name="city_id" id="city_id"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer"
                        @if($cities->isEmpty()) disabled @endif>
                        <option value="">{{ $cities->isEmpty() ? '— Select State First —' : '— Select City —' }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ (int)old('city_id', $pincode->city_id) === (int)$city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-xs"></i>
                    <span id="city_loader" class="hidden absolute right-9 top-1/2 -translate-y-1/2">
                        <i class="fas fa-circle-notch fa-spin text-amber-500 text-xs"></i>
                    </span>
                </div>
            </div>

            {{-- Area (locality) --}}
            <div>
                <label for="area" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Area / Locality</label>
                <input type="text" name="area" id="area"
                    value="{{ old('area', $pincode->area) }}" placeholder="e.g. Mansarovar, Andheri West"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            {{-- Short State --}}
            <div>
                <label for="short_state" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Short State</label>
                <input type="text" name="short_state" id="short_state"
                    value="{{ old('short_state', $pincode->short_state) }}" placeholder="e.g. MH"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

        </div>
    </div>

    {{-- Section: County & Community --}}
    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-blue-100 dark:bg-blue-500/10 flex items-center justify-center">
                <i class="fas fa-city text-blue-600 dark:text-blue-400 text-sm"></i>
            </span>
            <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">County & Community</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label for="county" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">County</label>
                <input type="text" name="county" id="county"
                    value="{{ old('county', $pincode->county) }}" placeholder="County name"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            <div>
                <label for="short_county" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Short County</label>
                <input type="text" name="short_county" id="short_county"
                    value="{{ old('short_county', $pincode->short_county) }}" placeholder="e.g. CNT"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            <div>
                <label for="community" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Community</label>
                <input type="text" name="community" id="community"
                    value="{{ old('community', $pincode->community) }}" placeholder="Community name"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            <div>
                <label for="short_community" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Short Community</label>
                <input type="text" name="short_community" id="short_community"
                    value="{{ old('short_community', $pincode->short_community) }}" placeholder="e.g. COM"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

        </div>
    </div>

    {{-- Section: Geo Coordinates --}}
    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm mb-6">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-white/5 flex items-center gap-3">
            <span class="w-8 h-8 rounded-xl bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                <i class="fas fa-globe text-emerald-600 dark:text-emerald-400 text-sm"></i>
            </span>
            <h2 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-widest">Geo Coordinates</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

            <div>
                <label for="latitude" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Latitude</label>
                <input type="text" name="latitude" id="latitude"
                    value="{{ old('latitude', $pincode->latitude) }}" placeholder="e.g. 19.0760"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            <div>
                <label for="longitude" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Longitude</label>
                <input type="text" name="longitude" id="longitude"
                    value="{{ old('longitude', $pincode->longitude) }}" placeholder="e.g. 72.8777"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

            <div>
                <label for="accuracy" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Accuracy</label>
                <input type="text" name="accuracy" id="accuracy"
                    value="{{ old('accuracy', $pincode->accuracy) }}" placeholder="e.g. 4"
                    class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
            </div>

        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex justify-end items-center gap-4">
        <a href="{{ route('pincodes.index') }}" class="px-6 py-3 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white transition-colors">
            Cancel
        </a>
        <button type="submit"
            class="inline-flex items-center justify-center px-8 py-3.5 text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all hover:scale-[1.02] active:scale-[0.98]">
            Save Changes <i class="fas fa-save ml-3"></i>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
const statesByCountryUrl = "{{ route('pincodes.states-by-country', ':id') }}";
const citiesByStateUrl   = "{{ route('pincodes.cities-by-state', ':id') }}";

// Saved IDs for edit mode (so AJAX refetch restores selections)
const savedStateId = {{ $pincode->state_id ?? 'null' }};
const savedCityId  = {{ $pincode->city_id ?? 'null' }};

function setLoading(loaderEl, selectEl, isLoading) {
    if (isLoading) {
        loaderEl.classList.remove('hidden');
        selectEl.disabled = true;
    } else {
        loaderEl.classList.add('hidden');
    }
}

function populateSelect(selectEl, items, placeholder, selectedId) {
    selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    items.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name;
        if (selectedId && item.id == selectedId) opt.selected = true;
        selectEl.appendChild(opt);
    });
    selectEl.disabled = items.length === 0;
}

document.getElementById('country_id').addEventListener('change', function () {
    const countryId = this.value;
    const stateSelect = document.getElementById('state_id');
    const citySelect  = document.getElementById('city_id');
    const stateLoader = document.getElementById('state_loader');

    // Reset city
    citySelect.innerHTML = '<option value="">— Select State First —</option>';
    citySelect.disabled = true;

    if (!countryId) {
        stateSelect.innerHTML = '<option value="">— Select Country First —</option>';
        stateSelect.disabled = true;
        return;
    }

    setLoading(stateLoader, stateSelect, true);

    fetch(statesByCountryUrl.replace(':id', countryId))
        .then(r => r.json())
        .then(states => {
            setLoading(stateLoader, stateSelect, false);
            // On country change (not initial load) don't restore saved state
            const restoreState = stateSelect.dataset.initialLoad === '1' ? savedStateId : null;
            stateSelect.dataset.initialLoad = '0';
            populateSelect(stateSelect, states,
                states.length ? '— Select State —' : '— No States Available —',
                restoreState
            );
            // If a state was restored, trigger cities
            if (restoreState && stateSelect.value) {
                stateSelect.dispatchEvent(new Event('change'));
            }
        })
        .catch(() => {
            setLoading(stateLoader, stateSelect, false);
            stateSelect.innerHTML = '<option value="">— Error Loading States —</option>';
            stateSelect.disabled = false;
        });
});

document.getElementById('state_id').addEventListener('change', function () {
    const stateId  = this.value;
    const citySelect  = document.getElementById('city_id');
    const cityLoader  = document.getElementById('city_loader');

    if (!stateId) {
        citySelect.innerHTML = '<option value="">— Select State First —</option>';
        citySelect.disabled = true;
        return;
    }

    setLoading(cityLoader, citySelect, true);

    const restoreCity = citySelect.dataset.initialLoad === '1' ? savedCityId : null;
    citySelect.dataset.initialLoad = '0';

    fetch(citiesByStateUrl.replace(':id', stateId))
        .then(r => r.json())
        .then(cities => {
            setLoading(cityLoader, citySelect, false);
            populateSelect(citySelect, cities,
                cities.length ? '— Select City —' : '— No Cities Available —',
                restoreCity
            );
        })
        .catch(() => {
            setLoading(cityLoader, citySelect, false);
            citySelect.innerHTML = '<option value="">— Error Loading Cities —</option>';
            citySelect.disabled = false;
        });
});

// On page load: if a country/state is pre-selected, trigger cascading reload
// (The controller already injects the right options server-side, so this is only
//  needed if the user changes the country — no auto-trigger needed on load)
// Mark state/city selects for initial-load restoration
document.getElementById('state_id').dataset.initialLoad = '0';
document.getElementById('city_id').dataset.initialLoad = '0';
</script>
@endpush
