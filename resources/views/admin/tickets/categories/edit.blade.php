@extends('layouts.app')

@section('header', 'Edit Category')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Edit Category: {{ $ticketCategory->name }}</h2>
        <a href="{{ route('admin.ticket-categories.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <form action="{{ route('admin.ticket-categories.update', $ticketCategory->id) }}" method="POST" class="p-8">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Category Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $ticketCategory->name) }}" 
                           class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-2 focus:ring-amber-500 outline-none transition-all" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select name="status" id="status" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 dark:bg-gray-800 focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                        <option value="1" {{ old('status', $ticketCategory->status) == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $ticketCategory->status) == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="pt-10">
                    <button type="submit" class="w-full flex justify-center py-3.5 px-8 border border-transparent rounded-2xl shadow-xl text-lg font-black text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Update Category <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
