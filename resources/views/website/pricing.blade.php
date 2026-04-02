@extends('layouts.public')
@section('title', 'Pricing - SetuGeo API')

@section('content')
<div class="bg-transparent py-24 sm:py-32" x-data="{ billingCycle: 'monthly' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-amber-500 font-bold tracking-wide uppercase text-sm">Pricing Plans</h2>
            <p class="mt-2 text-4xl font-extrabold text-white tracking-tight sm:text-5xl">Simple, transparent pricing</p>
            <p class="mt-4 max-w-2xl text-xl text-gray-400 mx-auto font-medium">No hidden fees, no surprise charges. Get exactly the data you need tailored to your scale.</p>
        </div>

        <!-- Billing Toggle -->
        <div class="flex justify-center mb-16">
            <div class="relative flex items-center p-1 bg-white/5 backdrop-blur-md rounded-2xl border border-white/10">
                <button @click="billingCycle = 'monthly'" 
                    :class="{ 'bg-amber-600 shadow-md text-white': billingCycle === 'monthly', 'text-gray-400 hover:text-white': billingCycle !== 'monthly' }" 
                    class="relative w-32 py-2 text-sm font-bold rounded-xl transition-all duration-300 focus:outline-none">
                    Monthly
                </button>
                <button @click="billingCycle = 'yearly'" 
                    :class="{ 'bg-amber-600 shadow-md text-white': billingCycle === 'yearly', 'text-gray-400 hover:text-white': billingCycle !== 'yearly' }" 
                    class="relative w-32 py-2 text-sm font-bold rounded-xl transition-all duration-300 focus:outline-none">
                    Yearly
                </button>
                <button @click="billingCycle = 'lifetime'" 
                    :class="{ 'bg-amber-600 shadow-md text-white': billingCycle === 'lifetime', 'text-gray-400 hover:text-white': billingCycle !== 'lifetime' }" 
                    class="relative w-32 py-2 text-sm font-bold rounded-xl transition-all duration-300 focus:outline-none">
                    Lifetime
                </button>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @foreach($plans as $plan)
                <div x-show="billingCycle === '{{ $plan->billing_cycle }}'" class="flex flex-col rounded-3xl bg-white/10 backdrop-blur-xl shadow-xl border border-white/10 p-8 hover:border-amber-500/50 hover:shadow-amber-500/10 transition-all duration-300 relative transform hover:-translate-y-1" x-transition>
                    
                    @if(str_contains(strtolower($plan->name), 'pro') || str_contains(strtolower($plan->name), 'gold') || str_contains(strtolower($plan->name), 'silver'))
                        <div class="absolute top-0 inset-x-0 h-2 bg-gradient-to-r from-amber-400 to-amber-600 rounded-t-3xl"></div>
                    @endif

                    @if(str_contains(strtolower($plan->name), 'gold') || str_contains(strtolower($plan->name), 'pro'))
                        <p class="absolute top-0 -translate-y-1/2 left-1/2 -translate-x-1/2 bg-amber-100 text-amber-800 text-[10px] font-black px-4 py-1.5 rounded-full border border-amber-200 uppercase tracking-widest shadow-sm">Most Popular</p>
                    @endif

                    <div class="mb-6">
                        <h3 class="text-2xl font-black text-white">{{ $plan->name }}</h3>
                        <p class="mt-3 text-sm text-gray-400 font-medium h-12 leading-relaxed">{{ $plan->terms ?? 'Access to our core APIs optimized for your application scale.' }}</p>
                    </div>

                    <div class="mb-8 flex items-baseline text-white border-b border-white/10 pb-8">
                        <span class="text-5xl font-extrabold tracking-tight">₹{{ number_format($plan->amount, 0) }}</span>
                        <span class="ml-1 text-lg font-bold text-gray-500">
                            @if($plan->billing_cycle === 'yearly') /yr
                            @elseif($plan->billing_cycle === 'monthly') /mo
                            @else /life
                            @endif
                        </span>
                    </div>

                    <ul role="list" class="flex-1 space-y-4 text-sm leading-6 text-gray-400 mb-8 font-medium">
                        <li class="flex gap-x-3 items-start">
                            <i class="fas fa-check-circle text-amber-500 mt-1"></i>
                            <span><strong class="text-white">{{ $plan->api_hits_limit ? number_format($plan->api_hits_limit) : 'Unlimited' }}</strong> API Requests</span>
                        </li>
                        @if($plan->benefits && is_array($plan->benefits))
                            @foreach($plan->benefits as $benefit)
                                <li class="flex gap-x-3 items-start">
                                    <i class="fas fa-check-circle text-amber-500 mt-1"></i>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        @endif
                    </ul>

                    <a href="{{ route('register') }}" class="mt-auto block w-full bg-white/5 border border-white/10 text-white hover:bg-amber-600 hover:border-amber-600 text-center font-bold py-3.5 px-4 rounded-xl transition-all duration-300 shadow-sm">
                        Get Started
                    </a>
                </div>
            @endforeach
        </div>

    </div>
</div>
@endsection
