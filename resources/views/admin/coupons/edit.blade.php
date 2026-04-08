@extends('layouts.app')

@section('header', 'Edit Coupon')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="{{ route('admin.coupons.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Edit Coupon</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-medium tracking-tight uppercase">Code: <span class="font-black text-amber-600 dark:text-amber-500 tracking-wider">{{ $coupon->code }}</span></p>
        </div>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="p-8 md:p-12">
            @csrf
            @method('PUT')
            <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Code -->
                    <div>
                        <label for="code" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Coupon Code</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $coupon->code) }}" required 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors uppercase" placeholder="e.g. SAVE20">
                        @error('code') <p class="mt-2 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Status</label>
                        <select name="status" id="status" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                            <option value="1" {{ old('status', $coupon->status) == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status', $coupon->status) == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <!-- Discount Type -->
                    <div>
                        <label for="discount_type" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Discount Type</label>
                        <select name="discount_type" id="discount_type" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                            <option value="percentage" {{ old('discount_type', $coupon->discount_type) == 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                            <option value="fixed" {{ old('discount_type', $coupon->discount_type) == 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                        </select>
                    </div>

                    <!-- Discount Value -->
                    <div>
                        <label for="discount_value" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Discount Value</label>
                        <input type="number" step="0.01" name="discount_value" id="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors" placeholder="e.g. 10">
                    </div>

                    <!-- Max Discount -->
                    <div>
                        <label for="max_discount" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Max Discount Amount (₹)</label>
                        <input type="number" step="0.01" name="max_discount" id="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors" placeholder="e.g. 500">
                        <p class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-tight">Only applicable for percentage discounts</p>
                    </div>

                    <!-- Max Redemptions -->
                    <div>
                        <label for="max_redemptions" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Max Global Redemptions</label>
                        <input type="number" name="max_redemptions" id="max_redemptions" value="{{ old('max_redemptions', $coupon->max_redemptions) }}" 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors" placeholder="e.g. 100">
                    </div>

                    <!-- Apply to Cycles -->
                    <div>
                        <label for="apply_to_cycles" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Apply to Billing Cycles</label>
                        <input type="number" name="apply_to_cycles" id="apply_to_cycles" value="{{ old('apply_to_cycles', $coupon->apply_to_cycles) }}" required 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors" placeholder="e.g. 1">
                        <p class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-tight">1 = First payment only. 999 = Lifetime</p>
                    </div>

                    <!-- Expiry Date -->
                    <div>
                        <label for="expires_at" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Expiry Date</label>
                        <input type="date" name="expires_at" id="expires_at" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}" 
                            class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Single Use Per User -->
                    <div>
                        <label for="single_use_per_user" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Single use per user</label>
                        <select name="single_use_per_user" id="single_use_per_user" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                            <option value="0" {{ old('single_use_per_user', $coupon->single_use_per_user) == '0' ? 'selected' : '' }}>No</option>
                            <option value="1" {{ old('single_use_per_user', $coupon->single_use_per_user) == '1' ? 'selected' : '' }}>Yes</option>
                        </select>
                        <p class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-tight">Limit to one redemption per account</p>
                    </div>

                    <!-- Plan Restriction -->
                    <div>
                        <label for="plan_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-2">Target Plan Restriction</label>
                        <select name="plan_id" id="plan_id" class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                            <option value="">All Plans (Universal)</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id', $coupon->plan_id) == $plan->id ? 'selected' : '' }}>{{ $plan->name }} ({{ ucfirst($plan->billing_cycle) }})</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-[10px] font-bold text-gray-400 uppercase tracking-tight">Select 'All Plans' to make the coupon universal</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-10 flex justify-end">
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Save Changes <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
