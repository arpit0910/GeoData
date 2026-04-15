@extends('layouts.app')

@section('content')
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Index</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium">Update metadata for <span class="text-amber-600">{{ $index->index_name }}</span></p>
        </div>
        <a href="{{ route('admin.indices.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to List
        </a>
    </div>

    <div class="max-w-2xl bg-white dark:bg-[#0f172a]/80 backdrop-blur-xl border border-gray-200 dark:border-white/5 rounded-2xl shadow-sm p-8">
        <form action="{{ route('admin.indices.update', $index->index_code) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Index Code (Read-only)</label>
                    <input type="text" value="{{ $index->index_code }}" disabled
                        class="w-full bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm text-gray-500 cursor-not-allowed">
                    <p class="mt-2 text-[10px] text-gray-400">Index codes are unique identifiers and cannot be changed after creation.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Index Name</label>
                    <input type="text" name="index_name" value="{{ old('index_name', $index->index_name) }}" required
                        class="w-full bg-gray-50 dark:bg-white/[0.02] border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-900 dark:text-white font-bold">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Exchange</label>
                        <select name="exchange" required
                            class="w-full bg-gray-50 dark:bg-white/[0.02] border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-900 dark:text-white font-bold">
                            <option value="NSE" {{ $index->exchange == 'NSE' ? 'selected' : '' }}>NSE (National Stock Exchange)</option>
                            <option value="BSE" {{ $index->exchange == 'BSE' ? 'selected' : '' }}>BSE (Bombay Stock Exchange)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-widest">Category</label>
                        <input type="text" name="category" value="{{ old('category', $index->category) }}" placeholder="e.g. Sectoral, Broad-based"
                            class="w-full bg-gray-50 dark:bg-white/[0.02] border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 transition-all text-gray-900 dark:text-white font-bold">
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-100 dark:border-white/5 flex gap-3">
                    <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-bold py-4 px-6 rounded-2xl shadow-xl shadow-amber-500/20 transition-all active:scale-95">
                        Save Changes
                    </button>
                    <a href="{{ route('admin.indices.index') }}" class="flex-1 bg-gray-100 dark:bg-white/5 text-gray-700 dark:text-gray-300 text-center font-bold py-4 px-6 rounded-2xl transition-all hover:bg-gray-200">
                        Discard
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection
