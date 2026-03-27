@extends('layouts.app')

@section('content')
<div class="py-12 bg-gray-50 rounded-xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">Pricing Plans</h2>
            <p class="mt-4 text-xl text-gray-600">Choose the perfect feature-rich plan for your APIs.</p>
        </div>

        <div class="mt-12 space-y-4 sm:mt-16 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-6 lg:max-w-4xl lg:mx-auto xl:max-w-none xl:mx-0 xl:grid-cols-3">
            @foreach($plans as $plan)
                <div class="border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-200 bg-white hover:shadow-lg transition-shadow duration-300">
                    <div class="p-6">
                        <h2 class="text-2xl leading-6 font-bold text-gray-900">{{ $plan->name }}</h2>
                        <p class="mt-4 text-sm text-gray-500 h-10">{{ $plan->terms }}</p>
                        <p class="mt-8">
                            <span class="text-4xl font-extrabold text-gray-900">₹{{ number_format($plan->amount - $plan->discount_amount, 2) }}</span>
                            <span class="text-base font-medium text-gray-500">/{{ rtrim($plan->billing_cycle, 'ly') }}</span>
                        </p>
                        
                        @if(Auth::user()->plan_id === $plan->id)
                            <button disabled class="mt-8 block w-full bg-green-500 border border-transparent rounded-md py-3 text-sm font-semibold text-white text-center cursor-not-allowed">
                                Current Active Plan
                            </button>
                        @else
                            <div class="mt-8">
                                <div class="flex space-x-2 mb-4">
                                    <input type="text" id="coupon_{{ $plan->id }}" class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border-gray-300 focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Coupon Code">
                                    <button onclick="applyCoupon({{ $plan->id }})" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-amber-700 bg-amber-100 hover:bg-amber-200 focus:outline-none">
                                        Apply
                                    </button>
                                </div>
                                <div id="coupon_msg_{{ $plan->id }}" class="text-xs mb-4 hidden"></div>
                                <div id="discount_details_{{ $plan->id }}" class="hidden mb-4 p-2 bg-green-50 rounded text-xs text-green-700">
                                    Discount: <span id="discount_val_{{ $plan->id }}"></span> | New Total: <span id="final_val_{{ $plan->id }}"></span>
                                </div>

                                <button id="buy_btn_{{ $plan->id }}" onclick="buyPlan({{ $plan->id }})" class="block w-full bg-amber-600 border border-transparent rounded-md py-3 text-sm font-semibold text-white text-center hover:bg-amber-700 transition-colors">
                                    Buy {{ $plan->name }}
                                </button>
                            </div>
                        @endif
                    </div>
                    <div class="pt-6 pb-8 px-6">
                        <h3 class="text-xs font-medium text-gray-900 tracking-wide uppercase">What's included</h3>
                        <ul role="list" class="mt-6 space-y-4">
                            <!-- API Hits display -->
                            <li class="flex space-x-3">
                                <i class="fas fa-check text-green-500 flex-shrink-0 h-5 w-5"></i>
                                <span class="text-sm text-gray-600 font-medium">{{ $plan->api_hits_limit ? number_format($plan->api_hits_limit) . ' API Hits/Month' : 'Unlimited API Hits' }}</span>
                            </li>

                            <!-- Dynamic benefits -->
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
</div>

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    let activeCoupons = {};

    function applyCoupon(planId) {
        let code = $(`#coupon_${planId}`).val();
        let msgDiv = $(`#coupon_msg_${planId}`);
        let detailsDiv = $(`#discount_details_${planId}`);

        if (!code) {
            alert('Please enter a coupon code.');
            return;
        }

        $.ajax({
            url: "{{ route('pricing.validate-coupon') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                code: code,
                plan_id: planId
            },
            success: function(response) {
                msgDiv.text(response.message).removeClass('hidden text-red-600').addClass('text-green-600');
                $(`#discount_val_${planId}`).text('₹' + response.discount_amount.toFixed(2));
                $(`#final_val_${planId}`).text('₹' + response.final_amount.toFixed(2));
                detailsDiv.removeClass('hidden');
                activeCoupons[planId] = response.coupon_id;
            },
            error: function(err) {
                let msg = err.responseJSON ? err.responseJSON.message : "Invalid coupon";
                msgDiv.text(msg).removeClass('hidden text-green-600').addClass('text-red-600');
                detailsDiv.addClass('hidden');
                delete activeCoupons[planId];
            }
        });
    }

    function buyPlan(planId) {
        let btn = $(`#buy_btn_${planId}`);
        let originalText = btn.text();
        btn.text('Processing...').prop('disabled', true);

        let postData = {
            _token: '{{ csrf_token() }}'
        };

        if (activeCoupons[planId]) {
            postData.coupon_id = activeCoupons[planId];
        }

        // 1. Fetch Order ID from backend
        $.ajax({
            url: `/pricing/${planId}/order`,
            type: 'POST',
            data: postData,
            success: function(response) {
                // Return button to normal state if order is fetched
                btn.text(originalText).prop('disabled', false);

                if (response.amount == 0) {
                    processVerification(planId, 'free_plan_' + Math.random(), 'free_plan', 'free_sig', postData.coupon_id);
                    return;
                }

                var options = {
                    "key": response.key, 
                    "amount": response.amount, 
                    "currency": "INR",
                    "name": "GeoData Subscriptions",
                    "description": "Plan Purchase",
                    "order_id": response.order_id, 
                    "handler": function (paymentResponse){
                        processVerification(planId, paymentResponse.razorpay_order_id, paymentResponse.razorpay_payment_id, paymentResponse.razorpay_signature, postData.coupon_id);
                    },
                    "prefill": {
                        "name": "{{ Auth::user()->first_name ?? 'User' }} {{ Auth::user()->last_name ?? '' }}",
                        "email": "{{ Auth::user()->email ?? '' }}",
                        "contact": "{{ Auth::user()->phone ?? '' }}"
                    },
                    "theme": {
                        "color": "#d97706"
                    }
                };
                
                var rzp1 = new Razorpay(options);
                rzp1.on('payment.failed', function (response){
                    alert("Payment Failed: " + response.error.description);
                });
                rzp1.open();
            },
            error: function(err) {
                btn.text(originalText).prop('disabled', false);
                alert("Could not initiate transaction. Check console/logs.");
                console.error(err);
            }
        });
    }

    function processVerification(planId, orderId, paymentId, signature, couponId) {
        let postData = {
            _token: '{{ csrf_token() }}',
            plan_id: planId,
            razorpay_order_id: orderId,
            razorpay_payment_id: paymentId,
            razorpay_signature: signature
        };

        if (couponId) {
            postData.coupon_id = couponId;
        }

        $.ajax({
            url: "{{ route('pricing.verify') }}",
            type: 'POST',
            data: postData,
            success: function(verifyRes) {
                if(verifyRes.success) {
                    alert(verifyRes.message);
                    window.location.reload();
                } else {
                    alert("Verification failed but payment might have succeeded.");
                }
            },
            error: function(err) {
                let msg = err.responseJSON ? err.responseJSON.message : "Failed to verify transaction";
                alert(msg);
            }
        });
    }
</script>
@endpush
@endsection
