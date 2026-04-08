@extends('layouts.app')

@section('header', 'Edit Plan')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <a href="{{ route('plans.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Back to Plans
        </a>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white mt-4 tracking-tight">Edit Plan</h1>
        <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400 leading-relaxed">Update details for <span class="text-amber-600 dark:text-amber-500 font-bold">{{ $plan->name }}</span>.</p>
    </div>

    <div class="bg-white dark:bg-richdark-surface rounded-3xl shadow-xl border border-gray-200 dark:border-white/5 overflow-hidden transition-all duration-300">
        <form action="{{ route('plans.update', $plan->id) }}" method="POST" class="p-8 md:p-12">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-8">
                <div class="space-y-2">
                    <label for="name" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Plan Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $plan->name) }}" required placeholder="e.g. Basic, Pro, Enterprise"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('name') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="gateway_product_id" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Gateway Product ID</label>
                    <input type="text" name="gateway_product_id" id="gateway_product_id" value="{{ old('gateway_product_id', $plan->gateway_product_id) }}" placeholder="e.g. prod_xyz123"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('gateway_product_id') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="billing_cycle" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Billing Cycle</label>
                    <select name="billing_cycle" id="billing_cycle" required class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors cursor-pointer">
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        <option value="lifetime" {{ old('billing_cycle', $plan->billing_cycle) == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                    </select>
                    @error('billing_cycle') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 pt-2"><p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Pricing & Limits</p></div>

                <div class="space-y-2">
                    <label for="amount" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Price Amount</label>
                    <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $plan->amount) }}" required placeholder="e.g. 499.00"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('amount') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="discount_amount" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Discount Amount</label>
                    <input type="number" step="0.01" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', $plan->discount_amount) }}" placeholder="e.g. 100.00"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    @error('discount_amount') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-2">
                    <label for="api_hits_limit" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">API Hits Limit</label>
                    <input type="number" name="api_hits_limit" id="api_hits_limit" value="{{ old('api_hits_limit', $plan->api_hits_limit) }}" placeholder="e.g. 50000 (blank = unlimited)"
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                    <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">Leave empty for unlimited API hits.</p>
                    @error('api_hits_limit') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 pt-2"><p class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-white/5 pb-3">Features & Conditions</p></div>

                <div class="md:col-span-2 space-y-2">
                    <label for="terms" class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">Terms & Description</label>
                    <textarea name="terms" id="terms" rows="3" placeholder="Brief description or terms of the plan..."
                        class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">{{ old('terms', $plan->terms) }}</textarea>
                    @error('terms') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>

                @php
                    $planBenefits = $plan->benefits && is_array($plan->benefits) && count($plan->benefits) > 0 ? $plan->benefits : [''];
                @endphp
                <div class="md:col-span-2" x-data="{ benefits: {{ json_encode(old('benefits', $planBenefits)) }} }">
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400 mb-3">Plan Benefits</label>
                    <template x-for="(benefit, index) in benefits" :key="index">
                        <div class="flex items-center mb-3">
                            <input type="text" x-model="benefits[index]" :name="'benefits[' + index + ']'" placeholder="e.g. Priority Support, Unlimited Exports"
                                class="flex-1 appearance-none px-4 py-3 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors">
                            <button type="button" @click="benefits.splice(index, 1)" class="ml-3 px-3 py-2 text-red-500 hover:text-red-700 focus:outline-none transition-colors" x-show="benefits.length > 1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="benefits.push('')" class="mt-2 text-sm text-amber-600 hover:text-amber-800 font-bold focus:outline-none transition-colors">
                        <i class="fas fa-plus mr-1"></i> Add Another Benefit
                    </button>
                    @error('benefits') <p class="mt-1 text-xs font-bold text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-12 flex flex-col md:flex-row justify-end items-center gap-4">
                <a href="{{ route('plans.index') }}" class="w-full md:w-auto px-8 py-3.5 text-sm font-black text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">Cancel</a>
                <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center px-10 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Update Plan <i class="fas fa-save ml-3 text-sm opacity-80"></i>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
