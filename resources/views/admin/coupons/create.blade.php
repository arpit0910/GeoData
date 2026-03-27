@extends('layouts.app')

@section('header', 'Create Coupon')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create New Coupon</h1>
            <p class="mt-1 text-sm text-gray-600">Define your discount rules and restrictions.</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to list
        </a>
    </div>

    <form action="{{ route('admin.coupons.store') }}" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
        @csrf
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Coupon Code</label>
                    <input type="text" name="code" id="code" value="{{ old('code') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm uppercase font-mono" placeholder="SUMMER25">
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="1" {{ old('status') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Discount Type -->
                <div>
                    <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount Type</label>
                    <select name="discount_type" id="discount_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                </div>

                <!-- Discount Value -->
                <div>
                    <label for="discount_value" class="block text-sm font-medium text-gray-700">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" id="discount_value" value="{{ old('discount_value') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="10.00">
                </div>

                <!-- Max Discount -->
                <div>
                    <label for="max_discount" class="block text-sm font-medium text-gray-700">Max Discount Amount (₹)</label>
                    <input type="number" step="0.01" name="max_discount" id="max_discount" value="{{ old('max_discount') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Optional">
                    <p class="mt-1 text-xs text-gray-400">Only applicable for percentage discounts.</p>
                </div>

                <!-- Max Redemptions -->
                <div>
                    <label for="max_redemptions" class="block text-sm font-medium text-gray-700">Max Global Redemptions</label>
                    <input type="number" name="max_redemptions" id="max_redemptions" value="{{ old('max_redemptions') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="∞">
                </div>

                <!-- Apply to Cycles -->
                <div>
                    <label for="apply_to_cycles" class="block text-sm font-medium text-gray-700">Apply to Billing Cycles</label>
                    <input type="number" name="apply_to_cycles" id="apply_to_cycles" value="{{ old('apply_to_cycles', 1) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-400">1 = First payment only. 999 = Lifetime.</p>
                </div>

                <!-- Expiry Date -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <!-- Single Use Per User -->
            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input id="single_use_per_user" name="single_use_per_user" type="checkbox" value="1" {{ old('single_use_per_user') ? 'checked' : '' }} class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                </div>
                <div class="ml-3 text-sm">
                    <label for="single_use_per_user" class="font-medium text-gray-700">Single use per user</label>
                    <p class="text-gray-500 text-xs">If checked, each user can only use this coupon once.</p>
                </div>
            </div>

            <!-- Plan Restriction -->
            <div>
                <label for="plan_id" class="block text-sm font-medium text-gray-700">Target Plan</label>
                <select name="plan_id" id="plan_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }} ({{ $plan->billing_cycle }})</option>
                    @endforeach
                </select>
                <p class="mt-2 text-xs text-gray-400 italic">Select 'All Plans' to make the coupon universal.</p>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Create Coupon
            </button>
        </div>
    </form>
</div>
@endsection
