@extends('layouts.app')

@section('content')
<div x-data="subscriptionCart()" class="py-12 bg-gray-50 rounded-xl relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Pricing Plans</h2>
            <p class="mt-4 text-xl text-gray-600">Choose the perfect feature-rich plan for your APIs.</p>

            <!-- Billing Toggle -->
            <div class="mt-8 flex justify-center">
                <div class="flex items-center p-1 bg-gray-200/60 dark:bg-white/5 backdrop-blur-md rounded-2xl border border-gray-300 dark:border-white/10">
                    <button @click="billingCycle = 'monthly'"
                        :class="billingCycle === 'monthly' ? 'bg-amber-600 shadow-md text-white' : 'text-gray-600 dark:text-gray-300 bg-white dark:bg-white/5 hover:text-amber-600 dark:hover:text-amber-400'"
                        class="w-32 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 focus:outline-none">
                        Monthly
                    </button>
                    <button @click="billingCycle = 'yearly'"
                        :class="billingCycle === 'yearly' ? 'bg-amber-600 shadow-md text-white' : 'text-gray-600 dark:text-gray-300 bg-white dark:bg-white/5 hover:text-amber-600 dark:hover:text-amber-400'"
                        class="w-32 py-2.5 text-sm font-bold rounded-xl transition-all duration-300 focus:outline-none relative">
                        Yearly
                        <span class="absolute -top-3 -right-3 bg-green-100 text-green-700 text-[10px] uppercase font-black px-2.5 py-1 rounded-full border border-green-200 shadow-sm animate-bounce">Save 20%</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-12 space-y-4 sm:mt-16 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-6 lg:max-w-4xl lg:mx-auto xl:max-w-none xl:mx-0 xl:grid-cols-3">
            @foreach($plans as $plan)
                <div x-show="billingCycle === '{{ $plan->billing_cycle }}'" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-200 bg-white hover:shadow-lg transition-shadow duration-300 flex flex-col">
                    <div class="p-6 flex-grow">
                        <h2 class="text-2xl leading-6 font-bold text-gray-900">{{ $plan->name }}</h2>
                        <p class="mt-4 text-sm text-gray-500 h-10">{{ $plan->terms }}</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">₹{{ number_format($plan->amount - $plan->discount_amount, 0) }}</span>
                            <span class="text-base font-medium text-gray-500">
                                @if($plan->billing_cycle === 'yearly') /yr
                                @elseif($plan->billing_cycle === 'monthly') /mo
                                @else /life
                                @endif
                            </span>
                        </p>
                        
                        @php
                            $planEffectiveAmount = $plan->amount - ($plan->discount_amount ?? 0);
                            $isCurrentPlan = isset($activeSubscription) && $activeSubscription && $activeSubscription->plan_id == $plan->id;
                            $activePlanAmount = $activeSubscription?->plan?->amount ?? null;
                            $isUpgrade  = $activePlanAmount !== null && $planEffectiveAmount > $activePlanAmount;
                            $isDowngrade = $activePlanAmount !== null && $planEffectiveAmount < $activePlanAmount;
                        @endphp

                        @if($isCurrentPlan)
                            {{-- Active plan badge --}}
                            <button disabled class="mt-8 block w-full bg-gradient-to-r from-emerald-600 to-teal-500 border border-emerald-400/30 rounded-xl py-3 text-sm font-black text-white text-center cursor-not-allowed flex items-center justify-center gap-3 shadow-xl shadow-emerald-500/10 ring-1 ring-white/10 relative overflow-hidden">
                                <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/10 to-white/0 opacity-50"></div>
                                <span class="flex items-center gap-1.5 relative z-10 shrink-0">
                                    <i class="fas fa-crown text-amber-300 animate-pulse text-xs"></i>
                                    Active Plan
                                </span>
                                <span class="text-[9px] font-bold opacity-90 uppercase tracking-widest relative z-10 bg-black/20 px-2.5 py-1 rounded-lg border border-white/5 shrink-0">
                                    Exp: {{ \Carbon\Carbon::parse($activeSubscription->expires_at)->year > 2100 ? 'Lifetime' : \Carbon\Carbon::parse($activeSubscription->expires_at)->format('M d, Y') }}
                                </span>
                            </button>

                        @elseif($isDowngrade)
                            {{-- Lower price — disabled Buy Now --}}
                            <button disabled class="mt-8 block w-full bg-gray-100 border border-gray-200 rounded-xl py-3 text-sm font-black text-gray-400 text-center cursor-not-allowed flex items-center justify-center gap-2 opacity-60">
                                <i class="fas fa-arrow-down text-xs"></i>
                                Buy Now
                            </button>

                        @elseif($isUpgrade)
                            {{-- Higher price — Upgrade Now --}}
                            <button @click="selectPlan({{ json_encode($plan) }})" class="mt-8 block w-full bg-amber-600 hover:bg-amber-700 border border-transparent rounded-xl py-3 text-sm font-black text-white text-center transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-arrow-up text-xs"></i>
                                Upgrade Now
                            </button>

                        @elseif($planEffectiveAmount == 0)
                            {{-- Free plan --}}
                            <button type="button" onclick="activateFreePlanSubscribe({{ $plan->id }})" class="mt-8 block w-full bg-emerald-600 hover:bg-emerald-700 border border-transparent rounded-xl py-3 text-sm font-black text-white text-center transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-bolt text-xs"></i>
                                Activate Free Plan
                            </button>

                        @else
                            {{-- Paid plan, no active subscription --}}
                            <button @click="selectPlan({{ json_encode($plan) }})" class="mt-8 block w-full bg-amber-600 hover:bg-amber-700 border border-transparent rounded-xl py-3 text-sm font-semibold text-white text-center transition-colors">
                                Subscribe Now
                            </button>
                        @endif
                    </div>
                    <div class="pt-6 pb-8 px-6">
                        <h3 class="text-xs font-medium text-gray-900 tracking-wide uppercase">What's included</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <li class="flex space-x-3">
                                <i class="fas fa-check text-green-500 flex-shrink-0 h-5 w-5"></i>
                                <span class="text-sm text-gray-600 font-medium">{{ $plan->api_hits_limit ? number_format($plan->api_hits_limit) . ' API Hits/Month' : 'Unlimited API Hits' }}</span>
                            </li>
                            @if(is_array($plan->benefits))
                                @foreach($plan->benefits as $benefit)
                                    @if(!empty($benefit))
                                    <li class="flex space-x-3">
                                        <i class="fas fa-check text-green-500 flex-shrink-0 h-5 w-5"></i>
                                        <span class="text-sm text-gray-600">{{ $benefit }}</span>
                                    </li>
                                    @endif
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Slide-over Drawer (Side Cart) -->
    <div x-show="cartOpen" 
         x-transition:enter="transition ease-out duration-500"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-500"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-hidden" style="display: none;">
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="cartOpen = false"></div>

        <div class="absolute inset-y-0 right-0 max-w-full flex pl-10">
            <div x-show="cartOpen"
                 x-transition:enter="transform transition ease-in-out duration-500"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in-out duration-500"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full"
                 class="w-screen max-w-md">
                
                <div class="h-full flex flex-col bg-[#0a0a0a] shadow-2xl border-l border-white/10 relative">
                    <!-- Glassmorphic Background -->
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent pointer-events-none"></div>

                    <!-- Header -->
                    <div class="p-6 border-b border-white/10 flex items-center justify-between relative z-10">
                        <h2 class="text-xl font-bold text-white tracking-tight">Order Summary</h2>
                        <button @click="cartOpen = false" class="text-gray-400 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 space-y-8 relative z-10">
                        <!-- Selected Plan Info -->
                        <div x-show="selectedPlan" class="bg-white/5 rounded-xl p-5 border border-white/5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-amber-500 font-bold text-lg" x-text="selectedPlan.name"></h3>
                                    <p class="text-gray-400 text-xs mt-1" x-text="selectedPlan.billing_cycle.charAt(0).toUpperCase() + selectedPlan.billing_cycle.slice(1)"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-white font-bold text-lg" x-text="'₹' + parseFloat(selectedPlan.amount - selectedPlan.discount_amount).toLocaleString('en-IN')"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Coupon Section -->
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-300">Have a coupon?</label>
                            <div class="flex gap-2">
                                <input type="text" x-model="couponCode" placeholder="Enter code" 
                                       class="flex-1 bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white focus:ring-amber-500 focus:border-amber-500 transition-all outline-none">
                                <button @click="applyCoupon" :disabled="loading"
                                        class="bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-lg transition-all">
                                    <span x-show="!loading">Apply</span>
                                    <i x-show="loading" class="fas fa-circle-notch fa-spin"></i>
                                </button>
                            </div>
                            <p x-show="couponMessage" :class="couponId ? 'text-green-400' : 'text-red-400'" class="text-xs font-medium" x-text="couponMessage"></p>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="border-t border-white/10 pt-6 space-y-3">
                            <div class="flex justify-between text-gray-400 text-sm">
                                <span>Subtotal</span>
                                <span x-text="'₹' + parseFloat(selectedPlan?.amount - selectedPlan?.discount_amount || 0).toLocaleString('en-IN')"></span>
                            </div>
                            <div x-show="discountAmount > 0" class="flex justify-between text-green-400 text-sm">
                                <span>Discount</span>
                                <span x-text="'- ₹' + parseFloat(discountAmount).toLocaleString('en-IN')"></span>
                            </div>
                            <div class="flex justify-between text-white font-bold text-xl pt-3">
                                <span>Total Payable</span>
                                <span class="text-amber-500" x-text="'₹' + parseFloat(finalAmount).toLocaleString('en-IN')"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 border-t border-white/10 relative z-10">
                        <button @click="initiatePayment" :disabled="loading"
                                class="w-full bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white font-bold py-4 rounded-xl shadow-lg shadow-amber-900/20 transition-all transform active:scale-[0.98] flex items-center justify-center gap-3">
                            <span x-text="loading ? 'Processing...' : (finalAmount > 0 ? 'Proceed to Payment' : 'Activate Free Plan')"></span>
                            <i class="fas fa-arrow-right" x-show="!loading"></i>
                            <i x-show="loading" class="fas fa-circle-notch fa-spin"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Congratulations Modal -->
    <div x-show="showSuccessModal" 
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="display: none;"
         @keydown.escape.window="window.location.href='/profile'">
        
        <div class="absolute inset-0 bg-black/60 backdrop-blur-md"></div>

        <div x-show="showSuccessModal"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-[#0a0a0a] border border-white/10 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl relative z-10">
            
            <!-- Confetti/Glow Effect -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-amber-500/20 rounded-full blur-[80px]"></div>
            <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-amber-500/20 rounded-full blur-[80px]"></div>

            <div class="p-8 text-center">
                <div class="w-20 h-20 bg-amber-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg shadow-amber-500/20">
                    <i class="fas fa-check text-4xl text-white"></i>
                </div>

                <h2 class="text-3xl font-extrabold text-white mb-2 tracking-tight">Congratulations!</h2>
                <p class="text-gray-400 mb-8">Your <span class="text-amber-500 font-bold uppercase tracking-wide" x-text="successData.name"></span> plan is now active.</p>

                <div class="bg-white/5 rounded-2xl p-6 mb-8 text-left border border-white/5">
                    <div class="flex justify-between items-center mb-4 pb-4 border-b border-white/10">
                        <span class="text-gray-400 text-sm">Valid Until</span>
                        <span class="text-white font-bold" x-text="successData.expires_at"></span>
                    </div>
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-gray-400 text-sm">Monthly Credits</span>
                        <span class="text-amber-500 font-bold" x-text="successData.credits"></span>
                    </div>
                    
                    <h4 class="text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-4">Included Benefits</h4>
                    <ul class="space-y-3">
                        <template x-for="benefit in successData.benefits" :key="benefit">
                            <li class="flex items-center gap-3 text-gray-300 text-sm">
                                <i class="fas fa-check-circle text-amber-500 text-[12px]"></i>
                                <span x-text="benefit"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                <button @click="window.location.href='/profile'" 
                        class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-amber-900/20 transition-all transform active:scale-[0.98] outline-none">
                    Go to Your Profile
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    function subscriptionCart() {
        return {
            cartOpen: false,
            billingCycle: '{{ Auth::user()->plan && Auth::user()->plan->billing_cycle === "yearly" ? "yearly" : "monthly" }}',
            selectedPlan: null,
            couponCode: '',
            couponId: null,
            couponMessage: '',
            discountAmount: 0,
            finalAmount: 0,
            loading: false,
            showSuccessModal: false,
            successData: {
                name: '',
                expires_at: '',
                benefits: [],
                credits: ''
            },

            selectPlan(plan) {
                this.selectedPlan = plan;
                this.couponCode = '';
                this.couponId = null;
                this.couponMessage = '';
                this.discountAmount = 0;
                this.finalAmount = plan.amount - plan.discount_amount;
                this.cartOpen = true;
            },

            applyCoupon() {
                if (!this.couponCode) return;
                this.loading = true;
                $.ajax({
                    url: "{{ route('pricing.validate-coupon') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        code: this.couponCode,
                        plan_id: this.selectedPlan.id
                    },
                    success: (response) => {
                        this.couponMessage = response.message;
                        this.discountAmount = response.discount_amount;
                        this.finalAmount = response.final_amount;
                        this.couponId = response.coupon_id;
                        this.loading = false;
                    },
                    error: (err) => {
                        this.couponMessage = err.responseJSON ? err.responseJSON.message : "Invalid coupon";
                        this.discountAmount = 0;
                        this.finalAmount = this.selectedPlan.amount - this.selectedPlan.discount_amount;
                        this.couponId = null;
                        this.loading = false;
                    }
                });
            },

            initiatePayment() {
                this.loading = true;
                let postData = {
                    _token: '{{ csrf_token() }}',
                    coupon_id: this.couponId
                };

                $.ajax({
                    url: `/pricing/${this.selectedPlan.id}/order`,
                    type: 'POST',
                    data: postData,
                    success: (response) => {
                        this.loading = false;

                        if (response.amount == 0) {
                            this.processVerification('free_plan_' + Date.now() + '_' + Math.floor(Math.random() * 1000), 'free_plan', 'free_sig');
                            return;
                        }

                        var options = {
                            "key": response.key,
                            "amount": response.amount,
                            "currency": "INR",
                            "name": "SetuGeo Subscriptions",
                            "description": this.selectedPlan.name + " Plan",
                            "order_id": response.order_id,
                            "notes": {
                                "plan_id": this.selectedPlan.id,
                                "coupon_id": this.couponId
                            },
                            "handler": (paymentResponse) => {
                                this.processVerification(paymentResponse.razorpay_order_id, paymentResponse.razorpay_payment_id, paymentResponse.razorpay_signature);
                            },
                            "prefill": {
                                "name": "{{ Auth::user()->name ?? 'User' }}",
                                "email": "{{ Auth::user()->email ?? '' }}",
                                "contact": "{{ Auth::user()->phone ?? '' }}"
                            },
                            "theme": { "color": "#d97706" }
                        };
                        
                        var rzp1 = new Razorpay(options);
                        rzp1.on('payment.failed', (response) => {
                            alert("Payment Failed: " + response.error.description);
                        });
                        rzp1.open();
                    },
                    error: (err) => {
                        this.loading = false;
                        alert("Could not initiate transaction. Please try again.");
                    }
                });
            },

            processVerification(orderId, paymentId, signature) {
                this.loading = true;
                let postData = {
                    _token: '{{ csrf_token() }}',
                    plan_id: this.selectedPlan.id,
                    razorpay_order_id: orderId,
                    razorpay_payment_id: paymentId,
                    razorpay_signature: signature,
                    coupon_id: this.couponId
                };

                $.ajax({
                    url: "{{ route('pricing.verify') }}",
                    type: 'POST',
                    data: postData,
                    success: (verifyRes) => {
                        this.loading = false;
                        if(verifyRes.success) {
                            this.successData = verifyRes.plan_details;
                            this.showSuccessModal = true;
                            this.cartOpen = false;
                        } else {
                            alert("Verification failed but payment might have succeeded.");
                        }
                    },
                    error: (err) => {
                        this.loading = false;
                        let msg = err.responseJSON ? err.responseJSON.message : "Failed to verify transaction";
                        alert(msg);
                    }
                });
            }
        }
    }

    function activateFreePlanSubscribe(planId) {
        if (!confirm('Activate the Free Plan on your account?')) return;

        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Activating...';

        fetch(`/pricing/${planId}/order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ coupon_id: null })
        })
        .then(r => r.json())
        .then(orderRes => {
            if (orderRes.amount == 0) {
                return fetch('/pricing/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: planId,
                        razorpay_order_id: 'free_plan_' + Date.now(),
                        razorpay_payment_id: 'free_plan',
                        razorpay_signature: 'free_sig',
                        coupon_id: null
                    })
                });
            }
            throw new Error('Not a free plan.');
        })
        .then(r => r.json())
        .then(verifyRes => {
            if (verifyRes.success) {
                // Reuse the existing Alpine success modal
                const alpineEl = document.querySelector('[x-data="subscriptionCart()"]');
                if (alpineEl && alpineEl._x_dataStack) {
                    const data = Alpine.$data(alpineEl);
                    data.successData = verifyRes.plan_details;
                    data.showSuccessModal = true;
                } else {
                    window.location.href = '{{ route('dashboard') }}';
                }
            } else {
                alert('Activation failed. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bolt text-xs"></i> Activate Free Plan';
            }
        })
        .catch(() => {
            alert('Something went wrong. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-bolt text-xs"></i> Activate Free Plan';
        });
    }
</script>
@endpush
@endsection
