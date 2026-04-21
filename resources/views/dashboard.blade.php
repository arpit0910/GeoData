@extends('layouts.app')

@section('header', 'Dashboard')

@section('content')
    @if (auth()->user()->is_admin)
        <div
            class="bg-white dark:bg-[#161e2d] rounded-2xl shadow-sm border border-gray-200 dark:border-white/5 p-6 md:p-8 transition-all duration-500">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-8 gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Welcome to your <span
                            class="text-amber-600 dark:text-amber-500">Admin Console</span></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 font-medium">Real-time overview of the SetuGeo
                        ecosystem.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="px-3 py-1.5 bg-green-500/10 text-green-600 dark:text-green-500 text-xs font-bold rounded-lg border border-green-500/20 flex items-center">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                        System Operational
                    </span>
                    <a href="{{ route('user.create') }}"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-amber-600/20 inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add User
                    </a>
                </div>
            </div>

            <!-- Analytics Sections -->
            <div class="space-y-10">
                <!-- 1. GeoData Analytics -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-globe-asia text-amber-600 dark:text-amber-500 text-sm"></i>
                        <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Global
                            Geo-Infrastructure</h4>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        @php
                            $geoStats = [
                                [
                                    'label' => 'Countries',
                                    'count' => \App\Models\Country::count(),
                                    'icon' => 'fa-globe',
                                    'color' => 'blue',
                                ],
                                [
                                    'label' => 'Regions',
                                    'count' => \App\Models\Region::count(),
                                    'icon' => 'fa-map-marked-alt',
                                    'color' => 'indigo',
                                ],
                                [
                                    'label' => 'Sub-Regions',
                                    'count' => \App\Models\SubRegion::count(),
                                    'icon' => 'fa-map-pin',
                                    'color' => 'purple',
                                ],
                                [
                                    'label' => 'States',
                                    'count' => \App\Models\State::count(),
                                    'icon' => 'fa-map',
                                    'color' => 'pink',
                                ],
                                [
                                    'label' => 'Cities',
                                    'count' => \App\Models\City::count(),
                                    'icon' => 'fa-city',
                                    'color' => 'cyan',
                                ],
                                [
                                    'label' => 'Pincodes',
                                    'count' => \App\Models\Pincode::count(),
                                    'icon' => 'fa-mail-bulk',
                                    'color' => 'teal',
                                ],
                                [
                                    'label' => 'Timezones',
                                    'count' => \App\Models\Timezone::count(),
                                    'icon' => 'fa-clock',
                                    'color' => 'emerald',
                                ],
                            ];
                        @endphp
                        @foreach ($geoStats as $stat)
                            <div
                                class="bg-gray-50 dark:bg-white/[0.02] border border-gray-100 dark:border-white/5 rounded-xl p-4 transition-all hover:border-amber-500/30">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas {{ $stat['icon'] }} text-{{ $stat['color'] }}-500 opacity-80"></i>
                                </div>
                                <p
                                    class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter">
                                    {{ $stat['label'] }}</p>
                                <h5 class="text-lg font-black text-gray-900 dark:text-white mt-0.5">
                                    {{ number_format($stat['count']) }}</h5>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Business & Monetization -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-chart-pie text-amber-600 dark:text-amber-500 text-sm"></i>
                        <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Ecosystem
                            & Revenue</h4>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        @php
                            $bizStats = [
                                [
                                    'label' => 'Total Users',
                                    'count' => \App\Models\User::count(),
                                    'icon' => 'fa-users',
                                    'color' => 'amber',
                                ],
                                [
                                    'label' => 'Active Subs',
                                    'count' => \App\Models\Subscription::where('status', 'active')->count(),
                                    'icon' => 'fa-file-invoice-dollar',
                                    'color' => 'green',
                                ],
                                [
                                    'label' => 'Total Plans',
                                    'count' => \App\Models\Plan::count(),
                                    'icon' => 'fa-layer-group',
                                    'color' => 'orange',
                                ],
                                [
                                    'label' => 'Transactions',
                                    'count' => \App\Models\TransactionHistory::count(),
                                    'icon' => 'fa-history',
                                    'color' => 'rose',
                                ],
                                [
                                    'label' => 'Active Coupons',
                                    'count' => \App\Models\Coupon::where('status', true)->count(),
                                    'icon' => 'fa-ticket-alt',
                                    'color' => 'pink',
                                ],
                                [
                                    'label' => 'API Logs',
                                    'count' => \App\Models\ApiLog::count(),
                                    'icon' => 'fa-microchip',
                                    'color' => 'blue',
                                ],
                            ];
                        @endphp
                        @foreach ($bizStats as $stat)
                            <div
                                class="bg-{{ $stat['color'] }}-50/50 dark:bg-{{ $stat['color'] }}-500/5 border border-{{ $stat['color'] }}-100 dark:border-{{ $stat['color'] }}-500/10 rounded-xl p-4 transition-all hover:shadow-lg hover:-translate-y-0.5">
                                <div class="flex items-center justify-between mb-2">
                                    <i
                                        class="fas {{ $stat['icon'] }} text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-500"></i>
                                </div>
                                <p
                                    class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter">
                                    {{ $stat['label'] }}</p>
                                <h5 class="text-lg font-black text-gray-900 dark:text-white mt-0.5">
                                    {{ number_format($stat['count']) }}</h5>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 3. Financial & Support -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-shield-alt text-amber-600 dark:text-amber-500 text-sm"></i>
                        <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Finance &
                            Operations</h4>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
                        @php
                            $opStats = [
                                [
                                    'label' => 'Equities',
                                    'count' => \App\Models\Equity::count(),
                                    'icon' => 'fa-chart-line',
                                    'color' => 'blue',
                                ],
                                [
                                    'label' => 'Indices',
                                    'count' => \App\Models\Index::count(),
                                    'icon' => 'fa-arrow-trend-up',
                                    'color' => 'amber',
                                ],
                                [
                                    'label' => 'Banks',
                                    'count' => \App\Models\Bank::count(),
                                    'icon' => 'fa-university',
                                    'color' => 'indigo',
                                ],
                                [
                                    'label' => 'Branches',
                                    'count' => \App\Models\BankBranch::count(),
                                    'icon' => 'fa-code-branch',
                                    'color' => 'violet',
                                ],
                                [
                                    'label' => 'Currencies',
                                    'count' => \App\Models\CurrencyConversion::count(),
                                    'icon' => 'fa-exchange-alt',
                                    'color' => 'emerald',
                                ],
                                [
                                    'label' => 'Tickets',
                                    'count' => \App\Models\Ticket::count(),
                                    'icon' => 'fa-headset',
                                    'color' => 'orange',
                                ],
                                [
                                    'label' => 'Queries',
                                    'count' => \App\Models\WebsiteQuery::count(),
                                    'icon' => 'fa-question-circle',
                                    'color' => 'red',
                                ],
                                [
                                    'label' => 'FAQs',
                                    'count' => \App\Models\Faq::count(),
                                    'icon' => 'fa-info-circle',
                                    'color' => 'gray',
                                ],
                            ];
                        @endphp
                        @foreach ($opStats as $stat)
                            <div
                                class="bg-gray-50 dark:bg-white/[0.02] border border-gray-100 dark:border-white/5 rounded-xl p-4 transition-all hover:border-amber-500/30">
                                <div class="flex items-center justify-between mb-2">
                                    <i class="fas {{ $stat['icon'] }} text-{{ $stat['color'] }}-500 opacity-80"></i>
                                </div>
                                <p
                                    class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-tighter">
                                    {{ $stat['label'] }}</p>
                                <h5 class="text-lg font-black text-gray-900 dark:text-white mt-0.5">
                                    {{ number_format($stat['count']) }}</h5>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @else
        <div
            class="bg-white dark:bg-[#161e2d] rounded-[2rem] shadow-2xl border border-gray-100 dark:border-white/5 p-6 md:p-12 text-center max-w-4xl mx-auto mt-6 md:mt-10 transition-all duration-700 relative overflow-hidden group">
            <!-- Animated background element for premium feel -->
            <div
                class="absolute -top-24 -right-24 w-64 h-64 bg-amber-500/10 rounded-full blur-[100px] pointer-events-none group-hover:bg-amber-500/20 transition-all duration-1000">
            </div>

            <div class="relative z-10">
                <div
                    class="inline-flex justify-center items-center w-20 h-20 md:w-24 md:h-24 rounded-3xl bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-500 mb-8 border border-amber-100 dark:border-amber-500/20 transform rotate-3 hover:rotate-0 transition-transform duration-500 shadow-inner">
                    <i class="fas fa-rocket text-3xl md:text-4xl"></i>
                </div>
                <h3 class="text-2xl md:text-4xl font-black text-gray-900 dark:text-white tracking-tight mb-4">Welcome back,
                    {{ auth()->user()->first_name ?? auth()->user()->name }}!</h3>
                <p
                    class="text-base md:text-xl text-gray-500 dark:text-gray-400 max-w-2xl mx-auto font-medium leading-relaxed">
                    Your professional geo-platform is ready. Harness the power of global data at your fingertips.</p>

                @php
                    $dashboardSub = auth()
                        ->user()
                        ->subscriptions()
                        ->with('plan')
                        ->where('status', 'active')
                        ->where('expires_at', '>', now())
                        ->latest()
                        ->first();
                    $dashboardPlan = $dashboardSub?->plan ?? auth()->user()->plan;
                    $creditsExhausted =
                        $dashboardSub &&
                        $dashboardSub->available_credits <= 0 &&
                        !is_null($dashboardPlan?->api_hits_limit);
                @endphp

                @if ($creditsExhausted)
                    <!-- Top-up Alert -->
                    <div
                        class="mt-8 p-6 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-500/20 rounded-[2rem] flex flex-col md:flex-row items-center justify-between text-left gap-6 transition-all hover:border-red-500/40 shadow-xl shadow-red-500/5 group/topup-banner relative overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-red-600/5 to-transparent opacity-0 group-hover/topup-banner:opacity-100 transition-opacity duration-700">
                        </div>
                        <div class="flex items-center gap-5 relative z-10">
                            <div
                                class="w-14 h-14 rounded-2xl bg-red-100 dark:bg-red-500/20 flex items-center justify-center text-red-600 dark:text-red-500 flex-shrink-0 shadow-inner ring-1 ring-red-200/50 dark:ring-red-500/20">
                                <i class="fas fa-bolt text-xl animate-pulse"></i>
                            </div>
                            <div>
                                <h4 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tight">
                                    Credits Exhausted</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-0.5">Need more hits? Top
                                    up <span class="text-red-600 dark:text-red-500 font-bold">20,000 credits</span>
                                    instantly for just <span class="text-gray-900 dark:text-white font-black">₹100</span>.
                                </p>
                            </div>
                        </div>
                        <button id="topup-btn" onclick="handleTopup()"
                            class="group/btn relative inline-flex items-center px-8 py-4 border border-transparent text-lg font-black rounded-2xl text-white bg-red-600 hover:bg-red-500 shadow-xl shadow-red-600/20 hover:shadow-red-500/40 transition-all active:scale-95 whitespace-nowrap z-10">
                            <span class="relative z-10">Top up Now</span>
                            <i
                                class="fas fa-plus-circle ml-3 transition-transform group-hover/btn:scale-110 group-hover/btn:rotate-90 relative z-10"></i>
                        </button>
                    </div>
                @endif

                <div
                    class="mt-8 md:mt-12 p-6 md:p-10 bg-gray-50 dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-3xl flex flex-col md:flex-row items-center justify-between text-left gap-8 transition-all hover:border-amber-500/30 shadow-sm relative overflow-hidden group/plan-card">
                    <div
                        class="absolute top-0 right-0 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl -mr-16 -mt-16 group-hover/plan-card:bg-amber-500/10 transition-colors duration-1000">
                    </div>
                    <div class="relative z-10">
                        <h4 class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.3em] mb-3">
                            Your Active Ecosystem</h4>
                        <div class="flex items-center flex-wrap gap-3">
                            <span
                                class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ $dashboardPlan ? $dashboardPlan->name : 'Explorer' }}</span>
                            @if ($dashboardSub)
                                <span
                                    class="px-3 py-1 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-500 text-[10px] font-black uppercase tracking-widest border border-emerald-500/20 shadow-sm">Active</span>
                            @else
                                <span
                                    class="px-3 py-1 rounded-xl bg-gray-500/10 text-gray-500 dark:text-gray-400 text-[10px] font-black uppercase tracking-widest border border-gray-500/20">Inactive</span>
                            @endif
                            @if ($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0)
                                <span
                                    class="px-3 py-1 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-500 text-[10px] font-black uppercase tracking-widest border border-blue-500/20 shadow-sm">Free
                                    Tier</span>
                            @endif
                        </div>
                        @if ($dashboardSub)
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2 font-bold flex items-center">
                                <i class="fas fa-calendar-alt mr-2 opacity-60"></i>
                                @if (\Carbon\Carbon::parse($dashboardSub->expires_at)->year > 2100)
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
                    @if (!$dashboardSub || ($dashboardPlan && ($dashboardPlan->amount ?? 1) <= 0))
                        <a href="{{ route('subscription.pricing') }}"
                            class="group/btn relative inline-flex items-center px-10 py-5 border border-transparent text-lg font-black rounded-[1.5rem] text-white bg-gradient-to-br from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 shadow-[0_10px_30px_-10px_rgba(217,119,6,0.5)] hover:shadow-[0_15px_40px_-10px_rgba(217,119,6,0.6)] transition-all active:scale-95 whitespace-nowrap z-10">
                            Upgrade Account
                            <i
                                class="fas fa-crown ml-3 transition-transform group-hover/btn:scale-110 group-hover/btn:rotate-6"></i>
                        </a>
                    @else
                        <a href="{{ route('pricing') }}"
                            class="group/btn relative inline-flex items-center px-10 py-5 border border-amber-600/20 text-lg font-black rounded-[1.5rem] text-amber-600 dark:text-amber-500 bg-amber-600/5 hover:bg-amber-600 hover:text-white transition-all active:scale-95 whitespace-nowrap z-10 overflow-hidden">
                            <span class="relative z-10">Switch Plan</span>
                            <i
                                class="fas fa-exchange-alt ml-3 transition-transform group-hover/btn:translate-x-1 relative z-10"></i>
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
                            "handler": function(res) {
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
