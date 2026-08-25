@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('subscription-features.index') }}" class="text-sm font-bold text-amber-600 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Features
        </a>
        <h1 class="text-3xl font-black text-gray-900 mt-4 tracking-tight">Edit Subscription Feature</h1>
        <p class="mt-2 text-sm font-medium text-gray-500">Update feature metadata and activation status.</p>
    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden">
        <form action="{{ route('subscription-features.update', $subscriptionFeature->id) }}" method="POST" class="p-8 md:p-12 space-y-8">
            @csrf
            @method('PUT')
            <div class="space-y-2">
                <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $subscriptionFeature->name) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div class="space-y-2">
                <label for="key" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Key</label>
                <input type="text" name="key" id="key" value="{{ old('key', $subscriptionFeature->key) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            </div>
            <div class="space-y-2">
                <label for="description" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Description</label>
                <textarea name="description" id="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('description', $subscriptionFeature->description) }}</textarea>
            </div>
            <div class="space-y-2">
                <label for="is_active" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Status</label>
                <select name="is_active" id="is_active" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="1" {{ old('is_active', $subscriptionFeature->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $subscriptionFeature->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-4">
                <a href="{{ route('subscription-features.index') }}" class="px-6 py-3 text-sm font-black text-gray-500">Cancel</a>
                <button type="submit" class="px-8 py-3 bg-amber-600 text-white rounded-2xl font-black">Update Feature</button>
            </div>
        </form>
    </div>
</div>
@endsection
