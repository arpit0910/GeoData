@extends('layouts.app')

@section('header', 'Add New Sub Region')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('subregions.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Sub Regions
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Add New Sub Region</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">Create a new sub region under a parent region.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('subregions.store') }}" method="POST" class="p-8 md:p-12">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div class="space-y-2">
                    <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Sub Region Name</label>
                    <input type="text" name="name" id="name" required value="{{ old('name') }}" placeholder="e.g. Southern Asia, Northern Europe"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('name') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="region_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Parent Region</label>
                    <select name="region_id" id="region_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="">— Select Region —</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ old('region_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                    @error('region_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 space-y-2">
                    <label for="wiki_data_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">WikiData ID</label>
                    <input type="text" name="wiki_data_id" id="wiki_data_id" value="{{ old('wiki_data_id') }}" placeholder="e.g. Q771405"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('wiki_data_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-12 flex flex-col md:flex-row justify-end items-center gap-4">
                <a href="{{ route('subregions.index') }}" class="w-full md:w-auto px-8 py-3.5 text-sm font-black text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-10 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Save Sub Region <i class="fas fa-save ml-3 text-sm opacity-80"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
