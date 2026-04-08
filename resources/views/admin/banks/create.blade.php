@extends('layouts.app')

@section('header', 'Add New Bank')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('banks.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Banks
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Create New Bank</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 shadow-sm leading-relaxed">Add a new financial institution to the SetuGeo global network.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('banks.store') }}" method="POST" class="p-8 md:p-12">
            @csrf
            
            <div class="space-y-8">
                <div>
                    <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Bank Official Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g. State Bank of India" required 
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('name')
                        <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-6 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Save Bank Record <i class="fas fa-save ml-3 text-sm opacity-80"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
