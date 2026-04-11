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
    $creditsExhausted = $dashboardSub && $dashboardSub->available_credits <= 0 && !is_null($dashboardPlan?->api_hits_limit);
@endphp

@if($creditsExhausted)
<!-- Top-up Alert -->
<div class="mt-8 p-6 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-500/20 rounded-[2rem] flex flex-col md:flex-row items-center justify-between text-left gap-6 transition-all hover:border-red-500/40 shadow-xl shadow-red-500/5 group/topup-banner relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-red-600/5 to-transparent opacity-0 group-hover/topup-banner:opacity-100 transition-opacity duration-700"></div>
    <div class="flex items-center gap-5 relative z-10">
        <div class="w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 flex-shrink-0 shadow-inner ring-1 ring-red-200/50 dark:ring-red-500/20">
            <i class="fas fa-bolt text-xl animate-pulse"></i>
        </div>
        <div>
            <h4 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">Credits Exhausted</h4>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Need more hits? Top up <span class="text-red-600 dark:text-red-500 font-bold">20,000 credits</span> instantly for just <span class="text-gray-900 dark:text-white font-black">₹100</span>.</p>
        </div>
    </div>
    <button id="topup-btn" onclick="handleTopup()" class="group/btn relative inline-flex items-center px-8 py-4 border border-transparent text-lg font-black rounded-2xl text-white bg-red-600 hover:bg-red-500 shadow-xl shadow-red-600/20 hover:shadow-red-500/40 transition-all active:scale-95 whitespace-nowrap z-10">
        <span class="relative z-10">Top up Now</span>
        <i class="fas fa-plus-circle ml-3 transition-transform group-hover/btn:scale-110 group-hover/btn:rotate-90 relative z-10"></i>
    </button>
</div>
@endif

<div class="mt-8 md:mt-12 p-6 md:p-10 bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-3xl flex flex-col md:flex-row items-center justify-between text-left gap-8 transition-all hover:border-amber-500/30 shadow-sm relative overflow-hidden group/plan-card">
    <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl -mr-16 -mt-16 group-hover/plan-card:bg-amber-500/10 transition-colors duration-1000"></div>
    <div class="relative z-10">
        <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.3em] mb-3">Your Active Ecosystem</h4>
        <div class="flex items-center flex-wrap gap-3">
            <span class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ $dashboardPlan ? $dashboardPlan->name : 'Explorer' }}</span>
            @if($dashboardSub)
                <span class="px-3 py-1 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 shadow-sm">Active</span>
            @else
                <span class="px-3 py-1 rounded-xl bg-gray-500/10 text-gray-500 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest border border-gray-500/20">Inactive</span>
            @endif
            @if($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0)
                <span class="px-3 py-1 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-500 text-[10px] font-black uppercase tracking-widest border border-blue-500/20 shadow-sm">Free Tier</span>
            @endif
        </div>
        @if($dashboardSub)
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-bold flex items-center">
                <i class="fas fa-calendar-alt mr-2 opacity-60"></i>
                @if(\Carbon\Carbon::parse($dashboardSub->expires_at)->year > 2100)
                    LIFETIME ACCESS GRANTED
                @else
                    VALIID UNTIL {{ $dashboardSub->expires_at->format('M d, Y') }}
                @endif
            </p>
        @else
             <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-bold flex items-center">
                <i class="fas fa-info-circle mr-2 opacity-60"></i>
                UPGRADE TO UNLOCK PREMIUM GEO-ANALYTICS
            </p>
        @endif
    </div>
    @if(!$dashboardSub || ($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0))
    <a href="{{ route('subscription.pricing') }}" class="group/btn relative inline-flex items-center px-10 py-5 border border-transparent text-lg font-black rounded-[1.5rem] text-white bg-gradient-to-br from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 shadow-[0_10px_30px_-10px_rgba(217,119,6,0.5)] hover:shadow-[0_15px_40px_-10px_rgba(217,119,6,0.6)] transition-all active:scale-95 whitespace-nowrap z-10">
        Upgrade Account
        <i class="fas fa-crown ml-3 transition-transform group-hover/btn:scale-110 group-hover/btn:rotate-6"></i>
    </a>
    @else
    <a href="{{ route('pricing') }}" class="group/btn relative inline-flex items-center px-10 py-5 border border-amber-600/20 text-lg font-black rounded-[1.5rem] text-amber-600 dark:text-amber-500 bg-amber-600/5 hover:bg-amber-600 hover:text-white transition-all active:scale-95 whitespace-nowrap z-10 overflow-hidden">
        <span class="relative z-10">Switch Plan</span>
        <i class="fas fa-exchange-alt ml-3 transition-transform group-hover/btn:translate-x-1 relative z-10"></i>
    </a>
    @endif
</div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    function handleTopup() {
        const btn = document.getElementById('topup-btn');
        const originalHtml = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-3"></i> Processing...';

        $.ajax({
            url: "{{ route('pricing.topup.order') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.order_id) {
                    const options = {
                        "key": response.key,
                        "amount": response.amount,
                        "currency": "INR",
                        "name": "SetuGeo",
                        "description": "API Credit Top-up (20,000 Credits)",
                        "order_id": response.order_id,
                        "handler": function (res) {
                            verifyTopup(res);
                        },
                        "prefill": {
                            "name": "{{ auth()->user()->name }}",
                            "email": "{{ auth()->user()->email }}"
                        },
                        "theme": {
                            "color": "#d97706"
                        },
                        "modal": {
                            "onindex": function() {
                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            }
                        }
                    };
                    const rzp = new Razorpay(options);
                    rzp.open();
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                toastr.error(xhr.responseJSON?.message || 'Failed to create top-up order.');
            }
        });
    }

    function verifyTopup(paymentResponse) {
        $.ajax({
            url: "{{ route('pricing.topup.verify') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                razorpay_order_id: paymentResponse.razorpay_order_id,
                razorpay_payment_id: paymentResponse.razorpay_payment_id,
                razorpay_signature: paymentResponse.razorpay_signature
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Payment verification failed.');
            }
        });
    }
</script>
@endpush
