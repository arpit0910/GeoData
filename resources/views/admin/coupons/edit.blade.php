@extends('layouts.app')

@section('header', 'Edit Coupon')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Coupon: {{ $coupon->code }}</h1>
            <p class="mt-1 text-sm text-gray-600">Update your discount rules and restrictions.</p>
        </div>
        <a href="{{ route('admin.coupons.index') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
            <i class="fas fa-arrow-left mr-1"></i> Back to list
        </a>
    </div>

    <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="bg-white shadow-md rounded-lg overflow-hidden">
        @csrf
        @method('PUT')
        <div class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Coupon Code</label>
                    <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm uppercase font-mono" placeholder="e.g. SAVE20">
                    @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="1" {{ old('status', $coupon->status) == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('status', $coupon->status) == '0' ? 'selected' : '' }}>Inactive</option>
                </select>
                </div>

                <!-- Discount Type -->
                <div>
                    <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount Type</label>
                    <select name="discount_type" id="discount_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="percentage" {{ old('discount_type', $coupon->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('discount_type', $coupon->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                </div>

                <!-- Discount Value -->
                <div>
                    <label for="discount_value" class="block text-sm font-medium text-gray-700">Discount Value</label>
                    <input type="number" step="0.01" name="discount_value" id="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. 10">
                </div>

                <!-- Max Discount -->
                <div>
                    <label for="max_discount" class="block text-sm font-medium text-gray-700">Max Discount Amount (₹)</label>
                    <input type="number" step="0.01" name="max_discount" id="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. 500">
                </div>

                <!-- Max Redemptions -->
                <div>
                    <label for="max_redemptions" class="block text-sm font-medium text-gray-700">Max Global Redemptions</label>
                    <input type="number" name="max_redemptions" id="max_redemptions" value="{{ old('max_redemptions', $coupon->max_redemptions) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. 100">
                </div>

                <!-- Apply to Cycles -->
                <div>
                    <label for="apply_to_cycles" class="block text-sm font-medium text-gray-700">Apply to Billing Cycles</label>
                    <input type="number" name="apply_to_cycles" id="apply_to_cycles" value="{{ old('apply_to_cycles', $coupon->apply_to_cycles) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="e.g. 1">
                </div>

                <!-- Expiry Date -->
                <div>
                    <label for="expires_at" class="block text-sm font-medium text-gray-700">Expiry Date</label>
                    <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label for="single_use_per_user" class="block text-sm font-medium text-gray-700">Single use per user</label>
                <select name="single_use_per_user" id="single_use_per_user" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="0" {{ old('single_use_per_user', $coupon->single_use_per_user) == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('single_use_per_user', $coupon->single_use_per_user) == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>

            <div>
                <label for="plan_id" class="block text-sm font-medium text-gray-700">Target Plan</label>
                <select name="plan_id" id="plan_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Plans</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" {{ old('plan_id', $coupon->plan_id) == $plan->id ? 'selected' : '' }}>{{ $plan->name }} ({{ $plan->billing_cycle }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <button type="submit" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-bold rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Update Coupon
            </button>
        </div>
    </form>
</div>
@endsection
