@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
@if(auth()->user()->is_admin)
<div class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 p-6 md:p-8 transition-all duration-500">
    <div class="mb-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Welcome to your <span class="text-amber-600 dark:text-amber-500">Admin Console</span></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Real-time overview of the SetuGeo ecosystem.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Quick Stat Card 1 -->
        <div class="group bg-amber-50 dark:bg-amber-600/5 rounded-2xl p-6 border border-amber-100 dark:border-amber-500/10 flex items-center transition-all hover:shadow-lg hover:-translate-y-1">
            <div class="h-14 w-14 bg-amber-100 dark:bg-amber-500/20 rounded-xl flex items-center justify-center text-amber-600 dark:text-amber-500 mr-5 transition-transform">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Total Users</p>
                <h4 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ \App\Models\User::count() }}</h4>
            </div>
        </div>
        
        <!-- Quick Action Card -->
        <div class="group bg-green-50 dark:bg-green-600/5 rounded-2xl p-6 border border-green-100 dark:border-green-500/10 flex items-center transition-all hover:shadow-lg hover:-translate-y-1">
            <div class="h-14 w-14 bg-green-100 dark:bg-green-500/20 rounded-xl flex items-center justify-center text-green-600 dark:text-green-500 mr-5 transition-transform">
                <i class="fas fa-user-plus text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">Quick Action</p>
                <a href="{{ route('user.create') }}" class="text-green-700 dark:text-green-400 font-bold hover:underline mt-1 inline-flex items-center group/link">
                    Add New User <i class="fas fa-arrow-right text-[10px] ml-1.5 transition-transform group-hover/link:translate-x-1"></i>
                </a>
            </div>
        </div>

        <!-- System Info Card -->
        <div class="group bg-blue-50 dark:bg-blue-600/5 rounded-2xl p-6 border border-blue-100 dark:border-blue-500/10 flex items-center transition-all hover:shadow-lg hover:-translate-y-1">
            <div class="h-14 w-14 bg-blue-100 dark:bg-blue-500/20 rounded-xl flex items-center justify-center text-blue-600 dark:text-blue-500 mr-5 transition-transform">
                <i class="fas fa-server text-2xl"></i>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">System Status</p>
                <h4 class="text-xl font-extrabold text-blue-800 dark:text-blue-400 flex items-center">
                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                    Operational
                </h4>
            </div>
        </div>
    </div>
</div>
@else
<div class="bg-white dark:bg-[#161e2d] rounded-[2rem] shadow-2xl border border-gray-100 dark:border-white/5 p-6 md:p-12 text-center max-w-4xl mx-auto mt-6 md:mt-10 transition-all duration-700 relative overflow-hidden group">
    <!-- Animated background element for premium feel -->
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-amber-500/10 rounded-full blur-[100px] pointer-events-none group-hover:bg-amber-500/20 transition-all duration-1000"></div>
    
    <div class="relative z-10">
        <div class="inline-flex justify-center items-center w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 mb-8 border border-amber-100 dark:border-amber-500/20 transform rotate-3 hover:rotate-0 transition-transform duration-500 shadow-inner">
            <i class="fas fa-rocket text-3xl md:text-4xl"></i>
        </div>
        <h3 class="text-2xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-4">Welcome back, {{ auth()->user()->first_name ?? auth()->user()->name }}!</h3>
        <p class="text-base md:text-xl text-gray-500 dark:text-gray-400 max-w-2xl mx-auto font-medium leading-relaxed">Your professional geo-platform is ready. Harness the power of global data at your fingertips.</p>
        
@php
    $dashboardSub = auth()->user()->subscriptions()
        ->with('plan')
        ->where('status', 'active')
        ->where('expires_at', '>', now())
        ->latest()
        ->first();
    $dashboardPlan = $dashboardSub?->plan ?? auth()->user()->plan;
@endphp
<div class="mt-8 md:mt-12 p-6 md:p-10 bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-3xl flex flex-col md:flex-row items-center justify-between text-left gap-8 transition-all hover:border-amber-500/30">
    <div>
        <h4 class="text-sm font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-2">Current Plan</h4>
        <div class="flex items-center flex-wrap gap-2">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $dashboardPlan ? $dashboardPlan->name : 'No Active Plan' }}</span>
            @if($dashboardSub)
                <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20">Active</span>
            @endif
            @if($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0)
                <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-500 text-[10px] font-black uppercase tracking-widest border border-blue-500/20">Free</span>
            @endif
        </div>
        @if($dashboardSub)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-medium">
                @if(\Carbon\Carbon::parse($dashboardSub->expires_at)->year > 2100)
                    Lifetime access
                @else
                    Renews on {{ $dashboardSub->expires_at->format('M d, Y') }}
                @endif
            </p>
        @endif
    </div>
    @if(!$dashboardSub || ($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0))
    <a href="{{ route('subscription.pricing') }}" class="group/btn inline-flex items-center px-8 py-4 border border-transparent text-lg font-black rounded-2xl text-white bg-amber-600 hover:bg-amber-500 shadow-xl shadow-amber-600/20 hover:shadow-amber-500/40 transition-all active:scale-95 whitespace-nowrap">
        Upgrade Now
        <i class="fas fa-crown ml-3 transition-transform group-hover/btn:scale-110 group-hover/btn:rotate-12"></i>
    </a>
    @else
    <a href="{{ route('pricing') }}" class="group/btn inline-flex items-center px-8 py-4 border border-amber-600/30 text-lg font-black rounded-2xl text-amber-600 dark:text-amber-500 bg-amber-600/5 hover:bg-amber-600 hover:text-white transition-all active:scale-95 whitespace-nowrap">
        View Plans
        <i class="fas fa-arrow-right ml-3 transition-transform group-hover/btn:translate-x-1"></i>
    </a>
    @endif
</div>
    </div>
</div>
@endif
@endsection
