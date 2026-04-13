@extends('layouts.app')

@section('content')
<div class="mb-8">
    <a href="{{ route('equities.index') }}" class="text-sm font-bold text-gray-500 hover:text-indigo-600 transition-colors mb-2 inline-block">
        <i class="fas fa-arrow-left mr-1"></i> Back to Equities
    </a>
    <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Edit Equity</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Update company details and exchange symbols for {{ $equity->isin }}.</p>
</div>

<div class="max-w-2xl">
    <div class="bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm overflow-hidden p-8">
        <form action="{{ route('equities.update', $equity->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">ISIN (Read Only)</label>
                    <input type="text" value="{{ $equity->isin }}" disabled
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-400 cursor-not-allowed">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Company Name</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $equity->company_name) }}" required
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('company_name') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">NSE Symbol</label>
                    <input type="text" name="nse_symbol" value="{{ old('nse_symbol', $equity->nse_symbol) }}"
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('nse_symbol') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">BSE Symbol</label>
                    <input type="text" name="bse_symbol" value="{{ old('bse_symbol', $equity->bse_symbol) }}"
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('bse_symbol') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Industry</label>
                    <input type="text" name="industry" value="{{ old('industry', $equity->industry) }}"
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('industry') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">Face Value</label>
                    <input type="number" step="0.01" name="face_value" value="{{ old('face_value', $equity->face_value) }}"
                        class="w-full bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('face_value') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Status</label>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input type="radio" name="is_active" value="1" class="sr-only" {{ old('is_active', $equity->is_active) == 1 ? 'checked' : '' }}>
                                <div class="w-5 h-5 border-2 border-gray-300 dark:border-white/10 rounded-full group-hover:border-indigo-500 transition-all"></div>
                                <div class="absolute inset-1 bg-indigo-600 rounded-full scale-0 transition-transform peer-checked:scale-100 hidden"></div>
                            </div>
                            <span class="ml-2 text-sm font-bold {{ old('is_active', $equity->is_active) == 1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">Active</span>
                        </label>
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input type="radio" name="is_active" value="0" class="sr-only" {{ old('is_active', $equity->is_active) == 0 ? 'checked' : '' }}>
                                <div class="w-5 h-5 border-2 border-gray-300 dark:border-white/10 rounded-full group-hover:border-indigo-500 transition-all"></div>
                            </div>
                            <span class="ml-2 text-sm font-bold {{ old('is_active', $equity->is_active) == 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">Inactive</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/5">
                <button type="submit"
                    class="px-8 py-3 text-sm font-black rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-lg hover:scale-[1.02] active:scale-[0.98]">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    input[type="radio"]:checked + div { border-color: #4f46e5; }
    input[type="radio"]:checked + div::after { content: ''; position: absolute; width: 0.5rem; height: 0.5rem; background: #4f46e5; border-radius: 50%; top: 50%; left: 50%; transform: translate(-50%, -50%); }
</style>

@endsection
