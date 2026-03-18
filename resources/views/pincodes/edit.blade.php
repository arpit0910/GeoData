@extends('layouts.app')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Pincode</h1>
    </div>
    <div class="flex items-center space-x-4">
        <a href="{{ route('pincodes.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i> Back to Pincodes
        </a>
    </div>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-6 bg-white border-b border-gray-200">
        @if ($errors->any())
            <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <ul class="text-sm text-red-700 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('pincodes.update', $pincode) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Postal Code -->
                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code *</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $pincode->postal_code) }}" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <!-- Country -->
                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700">Country *</label>
                    <select name="country_id" id="country_id" required class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm select2">
                        <option value="">Select a country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" {{ old('country_id', $pincode->country_id) == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- State -->
                <div>
                    <label for="state_id" class="block text-sm font-medium text-gray-700">State</label>
                    <select name="state_id" id="state_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm select2">
                        <option value="">Select a state</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $pincode->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- City -->
                <div>
                    <label for="city_id" class="block text-sm font-medium text-gray-700">City</label>
                    <select name="city_id" id="city_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm select2">
                        <option value="">Select a city</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" {{ old('city_id', $pincode->city_id) == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Additional Properties -->
                <div>
                    <label for="short_state" class="block text-sm font-medium text-gray-700">Short State</label>
                    <input type="text" name="short_state" id="short_state" value="{{ old('short_state', $pincode->short_state) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="county" class="block text-sm font-medium text-gray-700">County</label>
                    <input type="text" name="county" id="county" value="{{ old('county', $pincode->county) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="short_county" class="block text-sm font-medium text-gray-700">Short County</label>
                    <input type="text" name="short_county" id="short_county" value="{{ old('short_county', $pincode->short_county) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="community" class="block text-sm font-medium text-gray-700">Community</label>
                    <input type="text" name="community" id="community" value="{{ old('community', $pincode->community) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="short_community" class="block text-sm font-medium text-gray-700">Short Community</label>
                    <input type="text" name="short_community" id="short_community" value="{{ old('short_community', $pincode->short_community) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
                    <input type="text" name="latitude" id="latitude" value="{{ old('latitude', $pincode->latitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
                    <input type="text" name="longitude" id="longitude" value="{{ old('longitude', $pincode->longitude) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

                <div>
                    <label for="accuracy" class="block text-sm font-medium text-gray-700">Accuracy</label>
                    <input type="text" name="accuracy" id="accuracy" value="{{ old('accuracy', $pincode->accuracy) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                    Update Pincode
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'tailwind',
            width: '100%'
        });
    });
</script>
@endpush
