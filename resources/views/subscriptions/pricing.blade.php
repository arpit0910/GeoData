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
                            <button onclick="buyPlan({{ $plan->id }})" class="mt-8 block w-full bg-amber-600 border border-transparent rounded-md py-3 text-sm font-semibold text-white text-center hover:bg-amber-700 transition-colors">
                                Buy {{ $plan->name }}
                            </button>
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
    function buyPlan(planId) {
        let btn = $(event.target);
        let originalText = btn.text();
        btn.text('Processing...').prop('disabled', true);

        // 1. Fetch Order ID from backend
        $.ajax({
            url: `/pricing/${planId}/order`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Return button to normal state if order is fetched
                btn.text(originalText).prop('disabled', false);

                if (response.amount == 0) {
                    processVerification(planId, 'free_plan_' + Math.random(), 'free_plan', 'free_sig');
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
                        processVerification(planId, paymentResponse.razorpay_order_id, paymentResponse.razorpay_payment_id, paymentResponse.razorpay_signature);
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

    function processVerification(planId, orderId, paymentId, signature) {
        $.ajax({
            url: "{{ route('pricing.verify') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                plan_id: planId,
                razorpay_order_id: orderId,
                razorpay_payment_id: paymentId,
                razorpay_signature: signature
            },
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
