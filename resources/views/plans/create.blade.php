@extends('layouts.app')

@section('header')
    Add New Plan
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Add New Plan</h1>
    </div>
    <a href="{{ route('plans.index') }}" class="text-sm font-bold text-amber-600 dark:text-amber-500 hover:text-amber-700 transition-colors flex items-center">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('plans.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Name -->
                <div class="col-span-1 lg:col-span-1">
                    <label for="name" class="block text-sm font-medium text-gray-700">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. Basic, Pro">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Gateway Product ID -->
                <div class="col-span-1 lg:col-span-1">
                    <label for="gateway_product_id" class="block text-sm font-medium text-gray-700">Gateway Product ID</label>
                    <input type="text" name="gateway_product_id" id="gateway_product_id" value="{{ old('gateway_product_id') }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="e.g. prod_xyz123">
                    @error('gateway_product_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Billing Cycle -->
                <div class="col-span-1">
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700">Billing Cycle <span class="text-red-500">*</span></label>
                    <select name="billing_cycle" id="billing_cycle" required class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                        <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        <option value="lifetime" {{ old('billing_cycle') == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                    </select>
                    @error('billing_cycle')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Price & Limits Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Pricing & Limits</h3>
                </div>

                <!-- Amount -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700">Price Amount <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', '0.00') }}" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="0.00">
                    @error('amount')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Discount Amount -->
                <div>
                    <label for="discount_amount" class="block text-sm font-medium text-gray-700">Discount Amount</label>
                    <input type="number" step="0.01" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', '0.00') }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="0.00">
                    @error('discount_amount')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- API Hits Limit -->
                <div>
                    <label for="api_hits_limit" class="block text-sm font-medium text-gray-700">API Hits Limit</label>
                    <input type="number" name="api_hits_limit" id="api_hits_limit" value="{{ old('api_hits_limit') }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Leave blank for unlimited">
                    <p class="mt-1 text-xs text-gray-500">e.g. 50000. Leave empty for unlimited hits.</p>
                    @error('api_hits_limit')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Terms & Benefits Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Features & Conditions</h3>
                </div>

                <!-- Terms -->
                <div class="col-span-1 lg:col-span-3">
                    <label for="terms" class="block text-sm font-medium text-gray-700">Terms & Description</label>
                    <textarea name="terms" id="terms" rows="3" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Brief description or terms of the plan...">{{ old('terms') }}</textarea>
                    @error('terms')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Benefits (Alpine.js) -->
                <div class="col-span-1 lg:col-span-3" x-data="{ benefits: {{ json_encode(old('benefits', [''])) }} }">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plan Benefits</label>
                    <template x-for="(benefit, index) in benefits" :key="index">
                        <div class="flex items-center mb-2">
                            <input type="text" x-model="benefits[index]" :name="'benefits[' + index + ']'" class="flex-1 focus:ring-amber-500 focus:border-amber-500 block shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter a benefit (e.g. Priority Support)">
                            <button type="button" @click="benefits.splice(index, 1)" class="ml-2 px-3 py-2 text-red-500 hover:text-red-700 focus:outline-none" x-show="benefits.length > 1">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </template>
                    <button type="button" @click="benefits.push('')" class="mt-2 text-sm text-amber-600 hover:text-amber-800 font-medium focus:outline-none">
                        <i class="fas fa-plus mr-1"></i> Add Another Benefit
                    </button>
                    @error('benefits')<span class="text-red-500 text-xs block mt-1">{{ $message }}</span>@enderror
                </div>
            </div>

                <div class="mt-10 flex justify-end items-center space-x-6">
                    <a href="{{ route('plans.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-8 py-3.5 border border-transparent text-sm font-black rounded-2xl shadow-xl text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-500/40 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        Save Plan <i class="fas fa-save ml-3 text-sm"></i>
                    </button>
                </div>
        </form>
    </div>
</div>
@endsection
