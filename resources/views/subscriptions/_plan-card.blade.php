<article class="flex h-full flex-col rounded-3xl border {{ $plan->name === 'All in one' ? 'border-amber-500/50 bg-amber-500/[0.08]' : 'border-white/10 bg-white/[0.04]' }} p-6 shadow-xl">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">{{ $plan->name }}</h2>
            <p class="mt-1 text-xs font-bold uppercase tracking-widest text-amber-500">{{ ucfirst($plan->billing_cycle) }} access</p>
        </div>
        @if($plan->name === 'All in one')
            <span class="rounded-full bg-amber-500/15 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-300">Complete</span>
        @endif
    </div>
    <div class="mt-6 flex items-end gap-1">
        <span class="text-lg font-bold text-gray-400">₹</span>
        <span class="text-4xl font-black text-white">{{ number_format($plan->amount - $plan->discount_amount, 0) }}</span>
        <span class="pb-1 text-sm text-gray-500">/{{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
    </div>
    <p class="mt-4 min-h-[3rem] text-sm leading-6 text-gray-400">{{ $plan->terms }}</p>
    <p class="mt-4 text-sm font-bold text-white">{{ $plan->api_hits_limit === null ? 'Unlimited API credits' : number_format($plan->api_hits_limit).' credits per month' }}</p>
    <ul class="mt-5 mb-7 flex-1 space-y-3 text-sm text-gray-300">
        @foreach($plan->resolvedBenefits() as $benefit)
            <li class="flex gap-3"><i class="fas fa-check mt-1 text-xs text-emerald-500"></i><span>{{ $benefit }}</span></li>
        @endforeach
    </ul>
    @auth
        @if($activeSubscription?->plan_id === $plan->id)
            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-emerald-500/15 px-4 py-3 text-sm font-black text-emerald-300">Current plan</button>
        @elseif($temporaryCheckoutEnabled || $paymentCheckoutEnabled)
            <button type="button" data-plan-id="{{ $plan->id }}" data-plan-name="{{ $plan->name }} ({{ ucfirst($plan->billing_cycle) }})" class="js-buy-plan w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-black text-white transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-60">
                {{ $temporaryCheckoutEnabled ? 'Activate plan' : 'Purchase plan' }}
            </button>
        @else
            <button type="button" disabled class="w-full cursor-not-allowed rounded-xl bg-white/10 px-4 py-3 text-sm font-black text-gray-400">Checkout unavailable</button>
        @endif
    @else
        <a href="{{ route('login') }}" class="block w-full rounded-xl bg-amber-600 px-4 py-3 text-center text-sm font-black text-white transition hover:bg-amber-500">Log in to continue</a>
    @endauth
</article>
