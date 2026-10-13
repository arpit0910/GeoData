@extends('layouts.public')
@section('title', 'Mutual Fund Details by ISIN - SetuGeo Markets')
@section('meta_description', 'Look up an Indian mutual fund by ISIN and view its scheme details, NAV history, returns and available transaction metadata.')
@section('market_heading', 'Mutual fund research')
@section('market_subheading', 'Enter a scheme ISIN to inspect every verified detail currently available in SetuGeo.')
@section('content')
<div class="market-shell">
    @include('website.partials.market-nav')
    <div class="market-wrap py-5 sm:py-8">
        <form method="GET" action="{{ route('market.mutual-funds.details') }}" class="market-panel mx-auto flex max-w-3xl flex-col gap-3 p-4 sm:flex-row">
            <label class="sr-only" for="mf-isin">Mutual fund ISIN</label>
            <input id="mf-isin" class="market-input flex-1 uppercase" type="search" name="isin" value="{{ $isin }}" maxlength="12" placeholder="Enter ISIN, for example INF109K01Q49" required>
            <button class="market-button shrink-0" type="submit"><i class="fas fa-search mr-2"></i>View details</button>
        </form>

        @if($error)
            <div class="market-panel mx-auto mt-5 max-w-3xl border-rose-500/30 p-5 text-sm text-rose-300">{{ $error }}</div>
        @elseif(!$fund)
            <div class="market-panel mx-auto mt-5 max-w-3xl p-8 text-center">
                <i class="fas fa-chart-pie text-3xl text-amber-400"></i>
                <h2 class="mt-4 text-lg font-bold text-white">Search a mutual-fund scheme</h2>
                <p class="mt-2 text-sm market-muted">The result will combine AMFI scheme and NAV records with public Upstox instrument metadata. No investor portfolio data is requested.</p>
            </div>
        @else
            <div class="mt-6 grid gap-5 lg:grid-cols-3">
                <section class="market-panel p-5 lg:col-span-2">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><p class="market-kicker">{{ $fund['category'] ?: 'Mutual fund' }}</p><h2 class="mt-2 text-2xl font-extrabold text-white">{{ $fund['scheme_name'] }}</h2><p class="mt-2 text-sm market-muted">{{ $fund['amc_name'] ?: 'AMC unavailable' }}</p></div>
                        <span class="market-chip">{{ $fund['is_active'] ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <dl class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach(['ISIN' => $fund['isin'], 'Scheme code' => $fund['scheme_code'], 'Type' => $fund['type'], 'Sub-category' => $fund['sub_category']] as $label => $value)
                            <div class="rounded-xl bg-white/[.03] p-3"><dt class="text-[10px] font-bold uppercase tracking-wider market-muted">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-semibold text-white">{{ $value ?: 'Unavailable' }}</dd></div>
                        @endforeach
                    </dl>
                </section>

                <section class="market-panel p-5">
                    <p class="market-kicker">Latest NAV</p>
                    <div class="mt-3 text-3xl font-black text-white">{{ $fund['latest_nav'] !== null ? '₹'.number_format($fund['latest_nav'], 4) : 'Unavailable' }}</div>
                    <p class="mt-2 text-sm market-muted">{{ $fund['latest_nav_date'] ? \Carbon\Carbon::parse($fund['latest_nav_date'])->format('d M Y') : 'No NAV date' }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-2 text-sm"><div class="rounded-lg bg-white/[.03] p-3"><span class="market-muted">History low</span><strong class="mt-1 block text-white">{{ $fund['nav_history']['lowest_nav'] !== null ? '₹'.number_format($fund['nav_history']['lowest_nav'], 4) : '—' }}</strong></div><div class="rounded-lg bg-white/[.03] p-3"><span class="market-muted">History high</span><strong class="mt-1 block text-white">{{ $fund['nav_history']['highest_nav'] !== null ? '₹'.number_format($fund['nav_history']['highest_nav'], 4) : '—' }}</strong></div></div>
                    <p class="mt-3 text-xs market-muted">{{ number_format($fund['nav_history']['observations']) }} stored NAV observations</p>
                </section>
            </div>

            <section class="market-panel mt-5 p-5">
                <div class="flex items-center justify-between gap-3"><div><p class="market-kicker">Performance</p><h2 class="mt-1 text-lg font-bold text-white">NAV returns</h2></div><span class="text-xs market-muted">Calculated from stored NAV history</span></div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-9">
                    @forelse($fund['returns'] as $period => $return)
                        <div class="rounded-xl bg-white/[.03] p-3 text-center"><div class="text-[10px] font-bold uppercase market-muted">{{ $period }}</div><div class="mt-1 font-extrabold {{ $return['return_pct'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $return['return_pct'] >= 0 ? '+' : '' }}{{ number_format($return['return_pct'], 2) }}%</div></div>
                    @empty
                        <p class="col-span-full text-sm market-muted">Return periods are not available until sufficient NAV history has been stored.</p>
                    @endforelse
                </div>
            </section>

            @if($fund['nav_history']['recent'])
                <section class="market-panel mt-5 overflow-hidden">
                    <div class="p-5"><p class="market-kicker">NAV history</p><h2 class="mt-1 text-lg font-bold text-white">Recent observations</h2></div>
                    <div class="overflow-x-auto"><table class="market-table min-w-full"><thead><tr><th>Date</th><th class="text-right">NAV</th></tr></thead><tbody>@foreach(array_reverse($fund['nav_history']['recent']) as $nav)<tr><td>{{ \Carbon\Carbon::parse($nav['date'])->format('d M Y') }}</td><td class="text-right font-bold text-white">₹{{ number_format($nav['nav'], 4) }}</td></tr>@endforeach</tbody></table></div>
                </section>
            @endif

            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <section class="market-panel p-5">
                    <p class="market-kicker">Scheme transactions</p><h2 class="mt-1 text-lg font-bold text-white">Public instrument details</h2>
                    <dl class="mt-4 divide-y divide-white/[.07]">
                        @forelse($fund['transaction_details'] as $key => $value)
                            <div class="flex items-start justify-between gap-4 py-2.5"><dt class="text-sm market-muted">{{ str($key)->replace('_', ' ')->title() }}</dt><dd class="max-w-[55%] break-words text-right text-sm font-semibold text-white">@if(is_bool($value)){{ $value ? 'Yes' : 'No' }}@elseif($value === null || $value === '')&mdash;@else{{ is_numeric($value) ? number_format((float) $value, 4) : $value }}@endif</dd></div>
                        @empty
                            <p class="py-4 text-sm market-muted">Upstox public instrument metadata has not been imported for this scheme.</p>
                        @endforelse
                    </dl>
                </section>

                <section class="market-panel p-5">
                    <p class="market-kicker">Coverage</p><h2 class="mt-1 text-lg font-bold text-white">Holdings and allocation</h2>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/[.05] p-4"><h3 class="font-bold text-white">Scheme holdings</h3><p class="mt-1 text-sm market-muted">{{ $fund['holdings']['reason'] }}</p></div>
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/[.05] p-4"><h3 class="font-bold text-white">Sector allocation</h3><p class="mt-1 text-sm market-muted">{{ $fund['sector_allocation']['reason'] }}</p></div>
                    </div>
                </section>
            </div>

            <section class="market-panel mt-5 p-5">
                <p class="market-kicker">Provenance</p><h2 class="mt-1 text-lg font-bold text-white">Data sources</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach(['Scheme' => $fund['sources']['scheme'], 'NAV' => $fund['sources']['nav'], 'Transaction details' => $fund['sources']['transaction_details'], 'Portfolio' => $fund['sources']['portfolio']] as $label => $source)
                        <div class="rounded-xl bg-white/[.03] p-3"><div class="text-[10px] font-bold uppercase tracking-wider market-muted">{{ $label }}</div><div class="mt-1 text-sm font-semibold text-white">{{ $source ?: 'Not available' }}</div></div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
