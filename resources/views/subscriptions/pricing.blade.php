@extends('layouts.public')

@section('title', 'Plans & Pricing | SetuGeo')
@section('meta_description', 'Choose a SetuGeo monthly or yearly API subscription plan.')
@section('robots', 'noindex, follow')

@section('content')
<main class="min-h-screen bg-[#080d16] pt-28 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-12">
            <p class="text-amber-500 text-xs font-black uppercase tracking-[0.25em] mb-3">Subscription plans</p>
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight">Choose the access your business needs</h1>
            <p class="mt-4 text-gray-400">Select a monthly or yearly plan. Your API access and credits are activated immediately after payment verification.</p>
        </div>

        @if(session('warning') || session('error'))
            <div class="max-w-3xl mx-auto mb-8 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-5 py-4 text-sm font-semibold text-amber-200">
                <i class="fas fa-circle-exclamation mr-2"></i>{{ session('warning') ?: session('error') }}
            </div>
        @endif

        @if($activeSubscription)
            <div class="max-w-3xl mx-auto mb-8 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-sm text-emerald-200">
                Your current plan is <strong>{{ $activeSubscription->plan?->name }}</strong> and remains active until {{ $activeSubscription->expires_at?->format('d M Y') }}.
            </div>
        @endif

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
            @forelse($plans as $plan)
                <article class="flex flex-col rounded-3xl border {{ $plan->name === 'All in one' ? 'border-amber-500/50 bg-amber-500/[0.08]' : 'border-white/10 bg-white/[0.04]' }} p-6 shadow-xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-white">{{ $plan->name }}</h2>
                            <p class="mt-1 text-xs font-bold uppercase tracking-widest text-amber-500">{{ ucfirst($plan->billing_cycle) }}</p>
                        </div>
                        @if($plan->billing_cycle === 'yearly')
                            <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-400">Yearly</span>
                        @endif
                    </div>

                    <div class="mt-6 flex items-end gap-1">
                        <span class="text-lg font-bold text-gray-400">₹</span>
                        <span class="text-4xl font-black text-white">{{ number_format($plan->amount, 0) }}</span>
                        <span class="pb-1 text-sm text-gray-500">/{{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
                    </div>

                    <p class="mt-4 min-h-[3rem] text-sm leading-6 text-gray-400">{{ $plan->terms }}</p>
                    <p class="mt-4 text-sm font-bold text-white">
                        {{ $plan->api_hits_limit === null ? 'Unlimited API credits' : number_format($plan->api_hits_limit).' credits per month' }}
                    </p>

                    <ul class="mt-5 mb-7 flex-1 space-y-3 text-sm text-gray-300">
                        @foreach($plan->resolvedBenefits() as $benefit)
                            <li class="flex gap-3"><i class="fas fa-check mt-1 text-xs text-emerald-500"></i><span>{{ $benefit }}</span></li>
                        @endforeach
                    </ul>

                    @auth
                        <button type="button" data-plan-id="{{ $plan->id }}" class="js-buy-plan w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-black text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-60">
                            Choose {{ $plan->name }}
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="block w-full rounded-xl bg-amber-600 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-amber-500">Log in to purchase</a>
                    @endauth
                </article>
            @empty
                <div class="md:col-span-2 xl:col-span-4 rounded-2xl border border-white/10 bg-white/[0.04] p-8 text-center text-gray-400">
                    No subscription plans are currently available.
                </div>
            @endforelse
        </div>

        <div id="checkoutMessage" class="hidden mt-8 max-w-3xl mx-auto rounded-2xl border px-5 py-4 text-sm font-semibold" role="alert"></div>
    </div>
</main>
@endsection

@auth
@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    const csrfToken = @json(csrf_token());
    const orderUrlTemplate = @json(route('pricing.order', ['plan' => '__PLAN__']));
    const verifyUrl = @json(route('pricing.verify'));
    const dashboardUrl = @json(route('dashboard'));

    function showCheckoutMessage(message, success = false) {
        const element = document.getElementById('checkoutMessage');
        element.textContent = message;
        element.className = `mt-8 max-w-3xl mx-auto rounded-2xl border px-5 py-4 text-sm font-semibold ${success ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-red-500/30 bg-red-500/10 text-red-200'}`;
    }

    async function postJson(url, payload = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || data.error || 'The request could not be completed.');
        return data;
    }

    document.querySelectorAll('.js-buy-plan').forEach((button) => {
        button.addEventListener('click', async () => {
            const planId = button.dataset.planId;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Preparing checkout…';

            try {
                const order = await postJson(orderUrlTemplate.replace('__PLAN__', planId));
                if (!window.Razorpay) throw new Error('Payment checkout could not be loaded. Please refresh and try again.');

                const checkout = new Razorpay({
                    key: order.key,
                    amount: order.amount,
                    currency: 'INR',
                    order_id: order.order_id,
                    name: 'SetuGeo',
                    description: 'API subscription',
                    handler: async (payment) => {
                        try {
                            const result = await postJson(verifyUrl, {
                                plan_id: planId,
                                razorpay_order_id: payment.razorpay_order_id,
                                razorpay_payment_id: payment.razorpay_payment_id,
                                razorpay_signature: payment.razorpay_signature,
                            });
                            showCheckoutMessage(result.message || 'Subscription activated successfully.', true);
                            window.setTimeout(() => window.location.assign(dashboardUrl), 900);
                        } catch (error) {
                            showCheckoutMessage(error.message);
                        }
                    },
                    modal: { ondismiss: () => { button.disabled = false; button.textContent = originalText; } },
                    theme: { color: '#d97706' },
                });
                checkout.on('payment.failed', (response) => {
                    showCheckoutMessage(response.error?.description || 'Payment failed. Please try again.');
                    button.disabled = false;
                    button.textContent = originalText;
                });
                checkout.open();
            } catch (error) {
                showCheckoutMessage(error.message);
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });
</script>
@endpush
@endauth
