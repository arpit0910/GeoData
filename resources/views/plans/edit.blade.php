@extends('layouts.app')

@section('header')
    Edit Plan
@endsection

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Edit Plan</h1>
    </div>
    <a href="{{ route('plans.index') }}" class="text-amber-600 hover:text-amber-800 text-sm font-medium flex items-center bg-white px-3 py-1.5 rounded border border-gray-200 shadow-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="p-8 border-b border-gray-200">
        <form action="{{ route('plans.update', $plan->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Name -->
                <div class="col-span-1 lg:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700">Plan Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $plan->name) }}" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('name')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Billing Cycle -->
                <div class="col-span-1">
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700">Billing Cycle <span class="text-red-500">*</span></label>
                    <select name="billing_cycle" id="billing_cycle" required class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) == 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) == 'yearly' ? 'selected' : '' }}>Yearly</option>
                        <option value="lifetime" {{ old('billing_cycle', $plan->billing_cycle) == 'lifetime' ? 'selected' : '' }}>Lifetime</option>
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
                    <input type="number" step="0.01" name="amount" id="amount" value="{{ old('amount', $plan->amount) }}" required class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('amount')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Discount Amount -->
                <div>
                    <label for="discount_amount" class="block text-sm font-medium text-gray-700">Discount Amount</label>
                    <input type="number" step="0.01" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', $plan->discount_amount) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    @error('discount_amount')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- API Hits Limit -->
                <div>
                    <label for="api_hits_limit" class="block text-sm font-medium text-gray-700">API Hits Limit</label>
                    <input type="number" name="api_hits_limit" id="api_hits_limit" value="{{ old('api_hits_limit', $plan->api_hits_limit) }}" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    <p class="mt-1 text-xs text-gray-500">Leave empty for unlimited hits.</p>
                    @error('api_hits_limit')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Terms & Benefits Section -->
                <div class="col-span-1 lg:col-span-3">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2 mb-2 mt-4">Features & Conditions</h3>
                </div>

                <!-- Terms -->
                <div class="col-span-1 lg:col-span-3">
                    <label for="terms" class="block text-sm font-medium text-gray-700">Terms & Description</label>
                    <textarea name="terms" id="terms" rows="3" class="mt-1 focus:ring-amber-500 focus:border-amber-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Brief description or terms of the plan...">{{ old('terms', $plan->terms) }}</textarea>
                    @error('terms')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
                </div>

                <!-- Benefits (Alpine.js) -->
                @php
                    // Ensure there's at least one empty input if benefits is empty or null
                    $planBenefits = $plan->benefits && is_array($plan->benefits) && count($plan->benefits) > 0 ? $plan->benefits : [''];
                @endphp
                <div class="col-span-1 lg:col-span-3" x-data="{ benefits: {{ json_encode(old('benefits', $planBenefits)) }} }">
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

            <div class="mt-8 border-t border-gray-200 pt-5">
                <div class="flex justify-end">
                    <a href="{{ route('plans.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Cancel
                    </a>
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Update Plan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
