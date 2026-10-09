@extends('layouts.public')

@section('title', 'Plans & Pricing | SetuGeo')
@section('meta_description', 'Choose a SetuGeo monthly or yearly API subscription plan.')
@section('robots', 'index, follow, max-image-preview:large')

@section('content')
<main class="min-h-screen bg-[#080d16] pt-28 pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center mb-12">
            <p class="text-amber-500 text-xs font-black uppercase tracking-[0.25em] mb-3">Subscription plans</p>
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight">Choose the access your business needs</h1>
            <p class="mt-4 text-gray-400">Choose monthly or yearly billing, then select the access that fits your business.</p>
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

        @if($temporaryCheckoutEnabled)
            <div class="mx-auto mb-8 max-w-3xl rounded-2xl border border-sky-500/30 bg-sky-500/10 px-5 py-4 text-center text-sm text-sky-100">
                Payment collection is temporarily disabled. You can select and activate a plan now without entering payment details.
            </div>
        @endif

        <div class="mb-10 flex justify-center">
            <div class="inline-flex rounded-2xl border border-white/10 bg-white/[0.04] p-1.5" role="tablist" aria-label="Billing cycle">
                <button type="button" class="js-billing-tab rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-black text-white shadow-lg transition" data-cycle="monthly" role="tab" aria-selected="true" aria-controls="monthly-plans">Monthly</button>
                <button type="button" class="js-billing-tab rounded-xl px-6 py-2.5 text-sm font-black text-gray-400 transition hover:text-white" data-cycle="yearly" role="tab" aria-selected="false" aria-controls="yearly-plans">Yearly</button>
            </div>
        </div>

        <div id="monthly-plans" class="js-plan-panel grid gap-6 md:grid-cols-2 xl:grid-cols-4" data-cycle="monthly" role="tabpanel">
            @forelse($monthlyPlans as $plan)
                @include('subscriptions._plan-card', ['plan' => $plan])
            @empty
                <div class="md:col-span-2 xl:col-span-4 rounded-2xl border border-white/10 bg-white/[0.04] p-8 text-center text-gray-400">No monthly plans are currently available.</div>
            @endforelse
        </div>

        <div id="yearly-plans" class="js-plan-panel hidden grid gap-6 md:grid-cols-2 xl:grid-cols-4" data-cycle="yearly" role="tabpanel">
            @forelse($yearlyPlans as $plan)
                @include('subscriptions._plan-card', ['plan' => $plan])
            @empty
                <div class="md:col-span-2 xl:col-span-4 rounded-2xl border border-white/10 bg-white/[0.04] p-8 text-center text-gray-400">No yearly plans are currently available.</div>
            @endforelse
        </div>

        <div id="checkoutMessage" class="hidden mt-8 max-w-3xl mx-auto rounded-2xl border px-5 py-4 text-sm font-semibold" role="alert"></div>
    </div>
</main>
@endsection

@push('scripts')
@auth
    @if($paymentCheckoutEnabled && !$temporaryCheckoutEnabled)
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif
@endauth
<script>
    document.querySelectorAll('.js-billing-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const cycle = tab.dataset.cycle;
            document.querySelectorAll('.js-billing-tab').forEach((item) => {
                const active = item === tab;
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                item.classList.toggle('bg-amber-600', active);
                item.classList.toggle('text-white', active);
                item.classList.toggle('shadow-lg', active);
                item.classList.toggle('text-gray-400', !active);
            });
            document.querySelectorAll('.js-plan-panel').forEach((panel) => panel.classList.toggle('hidden', panel.dataset.cycle !== cycle));
            window.history.replaceState({}, '', `${window.location.pathname}?billing=${cycle}`);
        });
    });

    const requestedCycle = new URLSearchParams(window.location.search).get('billing');
    if (requestedCycle === 'yearly') document.querySelector('.js-billing-tab[data-cycle="yearly"]')?.click();
</script>
@auth
<script>
    const csrfToken = @json(csrf_token());
    const orderUrlTemplate = @json(route('pricing.order', ['plan' => '__PLAN__']));
    // Avoid render-time failure if views are deployed before the route cache
    // has been rebuilt. The POST endpoint is still protected by auth + CSRF.
    const temporaryPurchaseUrlTemplate = @json(url('/pricing/plans/__PLAN__/purchase'));
    const verifyUrl = @json(route('pricing.verify'));
    const dashboardUrl = @json(route('dashboard'));
    const temporaryCheckoutEnabled = @json($temporaryCheckoutEnabled);

    function showCheckoutMessage(message, success = false) {
        const element = document.getElementById('checkoutMessage');
        element.textContent = message;
        element.className = `mt-8 max-w-3xl mx-auto rounded-2xl border px-5 py-4 text-sm font-semibold ${success ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-red-500/30 bg-red-500/10 text-red-200'}`;
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    async function postJson(url, payload = {}) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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
            button.textContent = temporaryCheckoutEnabled ? 'Activating…' : 'Preparing checkout…';

            try {
                if (temporaryCheckoutEnabled) {
                    const confirmed = window.confirm(`Activate ${button.dataset.planName}? No payment will be collected during the temporary checkout period.`);
                    if (!confirmed) {
                        button.disabled = false;
                        button.textContent = originalText;
                        return;
                    }

                    const result = await postJson(temporaryPurchaseUrlTemplate.replace('__PLAN__', planId));
                    showCheckoutMessage(result.message || 'Subscription activated successfully.', true);
                    window.setTimeout(() => window.location.assign(result.redirect_url || dashboardUrl), 900);
                    return;
                }

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
@endauth
@endpush
