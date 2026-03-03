@extends('layouts.app')

@section('header')
    Edit Sub Region: {{ $subRegion->name }}
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Sub Region: {{ $subRegion->name }}</h1>
    </div>
    <a href="{{ route('subregions.index') }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium flex items-center bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('subregions.update', $subRegion->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="col-span-1 md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" required value="{{ old('name', $subRegion->name) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter sub region name">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Region -->
                <div class="col-span-1 md:col-span-2">
                    <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                    <select id="region_id" name="region_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                        <option value="">-- Select a Region --</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id', $subRegion->region_id) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- WikiData ID -->
                <div class="col-span-1 md:col-span-2">
                    <label for="wikiDataId" class="block text-sm font-medium text-gray-700">WikiData ID</label>
                    <input type="text" name="wikiDataId" id="wikiDataId" value="{{ old('wikiDataId', $subRegion->wikiDataId) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('wikiDataId')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="mt-8 border-t border-gray-200 pt-5">
                <div class="flex justify-end">
                    <a href="{{ route('subregions.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Cancel
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Update Sub Region
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
