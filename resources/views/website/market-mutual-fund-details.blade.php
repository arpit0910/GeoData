@extends('layouts.public')
@section('title', 'Mutual Fund Details by ISIN - SetuGeo Markets')
@section('meta_description', 'Look up an Indian mutual fund by ISIN and view its scheme details, NAV history, returns and available transaction metadata.')
@section('market_heading', 'Mutual fund research')
@section('market_subheading', 'Enter a scheme ISIN to inspect every verified detail currently available in SetuGeo.')
@section('content')
<div class="market-shell">
    @include('website.partials.market-nav')

    <div class="market-wrap py-5 sm:py-8">
        <a href="{{ route('market.mutual-funds') }}" class="mb-4 inline-flex items-center text-sm font-semibold text-slate-300 hover:text-white">
            <i class="fas fa-arrow-left mr-2 text-amber-400"></i>All mutual funds
        </a>

        <form method="GET" action="{{ route('market.mutual-funds.details') }}" class="market-panel mx-auto max-w-3xl p-4 sm:p-5">
            <label class="mb-2 block text-sm font-bold text-white" for="mf-isin">Mutual fund ISIN</label>
            <p id="mf-isin-help" class="mb-3 text-xs leading-5 market-muted">Enter the 12-character growth or reinvestment ISIN. Portfolio or investor data is never requested.</p>
            <div class="flex flex-col gap-3 sm:flex-row">
                <input id="mf-isin" class="market-input flex-1 uppercase" type="search" name="isin" value="{{ $isin }}" maxlength="12" placeholder="For example, INF109K01Q49" aria-describedby="mf-isin-help" autocomplete="off" required>
                <button class="market-button shrink-0" type="submit"><i class="fas fa-search mr-2"></i>View details</button>
            </div>
        </form>

        @if($error)
            <div class="market-panel mx-auto mt-5 max-w-3xl border-rose-500/30 p-5 text-sm text-rose-200" role="alert">
                <div class="flex items-start gap-3"><i class="fas fa-circle-exclamation mt-0.5 text-rose-400"></i><div><strong class="block text-white">Unable to show this scheme</strong><span class="mt-1 block">{{ $error }}</span></div></div>
            </div>
        @elseif(!$fund)
            <div class="market-panel mx-auto mt-5 max-w-3xl p-8 text-center">
                <i class="fas fa-chart-pie text-3xl text-amber-400"></i>
                <h2 class="mt-4 text-lg font-bold text-white">Search a mutual-fund scheme</h2>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 market-muted">Scheme identity and NAV come from stored AMFI records. Public instrument metadata is included when it has been imported.</p>
            </div>
        @else
            @php
                $identity = [
                    'Primary ISIN' => $fund['isin'],
                    'Reinvestment ISIN' => $fund['isin_reinvest'],
                    'Scheme code' => $fund['scheme_code'],
                    'Type' => $fund['type'],
                    'Sub-category' => $fund['sub_category'],
                ];
            @endphp

            <div class="mt-6 grid gap-5 lg:grid-cols-3">
                <section class="market-panel p-5 sm:p-6 lg:col-span-2" aria-labelledby="mf-scheme-heading">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <p class="market-kicker">{{ $fund['category'] ?: 'Mutual fund' }}</p>
                            <h2 id="mf-scheme-heading" class="mt-2 break-words text-xl font-extrabold leading-tight text-white sm:text-2xl">{{ $fund['scheme_name'] }}</h2>
                            <p class="mt-2 break-words text-sm market-muted">{{ $fund['amc_name'] ?: 'AMC unavailable' }}</p>
                        </div>
                        <span class="market-chip self-start {{ $fund['is_active'] ? 'border border-emerald-500/30 text-emerald-300' : 'border border-slate-500/30' }}">{{ $fund['is_active'] ? 'Active scheme' : 'Inactive scheme' }}</span>
                    </div>

                    @if($fund['requested_isin'] !== $fund['isin'])
                        <div class="mt-5 rounded-xl border border-sky-500/20 bg-sky-500/[.06] px-4 py-3 text-sm text-sky-100">Matched by reinvestment ISIN <span class="font-mono font-bold">{{ $fund['requested_isin'] }}</span>.</div>
                    @endif

                    <dl class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($identity as $label => $value)
                            <div class="min-w-0 rounded-xl border border-white/[.05] bg-white/[.03] p-3">
                                <dt class="text-[10px] font-bold uppercase tracking-wider market-muted">{{ $label }}</dt>
                                <dd class="mt-1 break-all text-sm font-semibold text-white">{{ $value ?: 'Unavailable' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="market-panel p-5 sm:p-6" aria-labelledby="mf-nav-heading">
                    <p class="market-kicker">Latest NAV</p>
                    <h2 id="mf-nav-heading" class="mt-3 break-words text-3xl font-black text-white">{!! $fund['latest_nav'] !== null ? '&#8377;'.number_format($fund['latest_nav'], 4) : '<span class="text-xl">Unavailable</span>' !!}</h2>
                    <p class="mt-2 text-sm market-muted">{{ $fund['latest_nav_date'] ? \Carbon\Carbon::parse($fund['latest_nav_date'])->format('d M Y') : 'No NAV date stored' }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-2 text-sm">
                        <div class="min-w-0 rounded-lg bg-white/[.03] p-3"><span class="market-muted">History low</span><strong class="mt-1 block break-words text-white">{!! $fund['nav_history']['lowest_nav'] !== null ? '&#8377;'.number_format($fund['nav_history']['lowest_nav'], 4) : '&mdash;' !!}</strong></div>
                        <div class="min-w-0 rounded-lg bg-white/[.03] p-3"><span class="market-muted">History high</span><strong class="mt-1 block break-words text-white">{!! $fund['nav_history']['highest_nav'] !== null ? '&#8377;'.number_format($fund['nav_history']['highest_nav'], 4) : '&mdash;' !!}</strong></div>
                    </div>
                    <p class="mt-3 text-xs market-muted">{{ number_format($fund['nav_history']['observations']) }} stored NAV observations</p>
                </section>
            </div>

            <section class="market-panel mt-5 p-5 sm:p-6" aria-labelledby="mf-performance-heading">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="market-kicker">Performance</p><h2 id="mf-performance-heading" class="mt-1 text-lg font-bold text-white">NAV returns</h2></div>
                    <span class="text-xs market-muted">Calculated from stored NAV history</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                    @forelse($fund['returns'] as $period => $return)
                        <div class="rounded-xl border border-white/[.05] bg-white/[.03] p-3 text-center"><div class="text-[10px] font-bold uppercase market-muted">{{ $period }}</div><div class="mt-1 font-extrabold {{ $return['return_pct'] >= 0 ? 'text-emerald-400' : 'text-rose-400' }}">{{ $return['return_pct'] >= 0 ? '+' : '' }}{{ number_format($return['return_pct'], 2) }}%</div></div>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-white/10 p-4 text-sm leading-6 market-muted">Returns will appear after enough historical NAV observations have been stored.</div>
                    @endforelse
                </div>
            </section>

            <section class="market-panel mt-5 overflow-hidden" aria-labelledby="mf-history-heading">
                <div class="p-5 sm:p-6"><p class="market-kicker">NAV history</p><h2 id="mf-history-heading" class="mt-1 text-lg font-bold text-white">Recent observations</h2></div>
                @if($fund['nav_history']['recent'])
                    <div class="overflow-x-auto"><table class="market-table market-table-compact"><thead><tr><th>Date</th><th class="text-right">NAV</th></tr></thead><tbody>@foreach(array_reverse($fund['nav_history']['recent']) as $nav)<tr><td>{{ \Carbon\Carbon::parse($nav['date'])->format('d M Y') }}</td><td class="text-right font-bold text-white">&#8377;{{ number_format($nav['nav'], 4) }}</td></tr>@endforeach</tbody></table></div>
                @else
                    <p class="border-t border-white/[.07] px-5 py-5 text-sm market-muted sm:px-6">No NAV history has been stored for this scheme yet.</p>
                @endif
            </section>

            <section class="market-panel mt-5 p-5 sm:p-6" aria-labelledby="mf-transaction-heading">
                <p class="market-kicker">Scheme transactions</p><h2 id="mf-transaction-heading" class="mt-1 text-lg font-bold text-white">Public instrument details</h2>
                <dl class="mt-4 grid gap-x-8 md:grid-cols-2">
                    @forelse($fund['transaction_details'] as $key => $value)
                        <div class="flex flex-col gap-1 border-b border-white/[.07] py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-5">
                            <dt class="text-sm market-muted">{{ str($key)->replace('_', ' ')->title() }}</dt>
                            <dd class="break-words text-left text-sm font-semibold text-white sm:max-w-[58%] sm:text-right">@if(is_bool($value)){{ $value ? 'Yes' : 'No' }}@elseif($value === null || $value === '')&mdash;@else{{ $value }}@endif</dd>
                        </div>
                    @empty
                        <p class="col-span-full rounded-xl border border-dashed border-white/10 p-4 text-sm leading-6 market-muted">Public instrument metadata has not been imported for this scheme.</p>
                    @endforelse
                </dl>
            </section>

            <section class="market-panel mt-5 p-5 sm:p-6" aria-labelledby="mf-sources-heading">
                <p class="market-kicker">Provenance</p><h2 id="mf-sources-heading" class="mt-1 text-lg font-bold text-white">Data sources</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach(['Scheme' => $fund['sources']['scheme'], 'NAV' => $fund['sources']['nav'], 'Transaction details' => $fund['sources']['transaction_details'], 'Portfolio' => $fund['sources']['portfolio']] as $label => $source)
                        <div class="min-w-0 rounded-xl border border-white/[.05] bg-white/[.03] p-3"><div class="text-[10px] font-bold uppercase tracking-wider market-muted">{{ $label }}</div><div class="mt-1 break-words text-sm font-semibold text-white">{{ $source ?: 'Not available' }}</div></div>
                    @endforeach
                </div>
                @if($fund['sources']['upstox_synced_at'])
                    <p class="mt-3 text-xs market-muted">Instrument metadata last imported {{ \Carbon\Carbon::parse($fund['sources']['upstox_synced_at'])->format('d M Y, h:i A') }}.</p>
                @endif
            </section>
        @endif
    </div>
</div>
@endsection
