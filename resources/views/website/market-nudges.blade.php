@extends('layouts.public')
@section('title', 'Stock Market Nudges - SetuGeo Markets')
@section('meta_description', 'Browse the latest Indian stock market growth, fall and volume nudges generated from daily market data.')
@section('market_heading', 'Market nudges')
@section('market_subheading', 'Fresh signals for notable price moves, highs, lows and unusual trading volume.')

@section('content')
<div class="market-shell">
    @include('website.partials.market-nav')

    <main class="market-wrap py-5 sm:py-8">
        <form method="GET" class="market-panel mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_220px_180px_auto]">
            <div class="min-w-0">
                <label for="nudge-search" class="sr-only">Search nudges</label>
                <input id="nudge-search" class="market-input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by company, symbol, ISIN or signal">
            </div>
            <div>
                <label for="nudge-sector" class="sr-only">Sector</label>
                <select id="nudge-sector" class="market-input" name="sector">
                    <option value="">All sectors</option>
                    @foreach($sectors as $item)
                        <option value="{{ $item }}" @selected($sector === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="nudge-stock-category" class="sr-only">Stock category</label>
                <select id="nudge-stock-category" class="market-input" name="stock_category">
                    <option value="">All stock categories</option>
                    @foreach($stockCategories as $item)
                        <option value="{{ $item }}" @selected($stockCategory === $item)>{{ $item }}</option>
                    @endforeach
                </select>
            </div>
            @if($category !== 'all')
                <input type="hidden" name="category" value="{{ $category }}">
            @endif
            <button class="market-button" type="submit"><i class="fas fa-search mr-2"></i>Search nudges</button>
        </form>

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex max-w-full gap-2 overflow-x-auto pb-1" aria-label="Nudge categories">
                @foreach(array_merge(['all'], $categories) as $item)
                    <a href="{{ route('market.nudges', array_filter(['category' => $item === 'all' ? null : $item, 'search' => request('search'), 'sector' => $sector, 'stock_category' => $stockCategory])) }}"
                       class="shrink-0 rounded-full border px-4 py-2 text-xs font-extrabold uppercase tracking-wide transition-colors {{ $category === $item ? 'border-amber-400 bg-amber-400/10 text-amber-300' : 'border-white/10 bg-white/[.03] text-slate-400 hover:border-white/20 hover:text-white' }}">
                        {{ $item }}
                    </a>
                @endforeach
            </div>
            <p class="shrink-0 text-sm market-muted"><strong class="text-white">{{ number_format($nudges->total()) }}</strong> signals</p>
        </div>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Stock market nudges">
            @forelse($nudges as $nudge)
                @php
                    $nudgeCategory = \App\Models\StockNudge::categoryForRule($nudge->rule) ?? 'market';
                    $categoryStyle = match($nudgeCategory) {
                        'growth' => 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300',
                        'fall' => 'border-rose-400/25 bg-rose-400/10 text-rose-300',
                        'volume' => 'border-sky-400/25 bg-sky-400/10 text-sky-300',
                        default => 'border-slate-400/25 bg-slate-400/10 text-slate-300',
                    };
                    $icon = match($nudgeCategory) {
                        'growth' => 'fa-arrow-trend-up',
                        'fall' => 'fa-arrow-trend-down',
                        'volume' => 'fa-chart-column',
                        default => 'fa-bell',
                    };
                    $symbol = $nudge->index_code ?: ($nudge->equity?->nse_symbol ?: $nudge->equity?->bse_symbol);
                    $observedAt = $nudge->observed_at?->copy()->timezone('Asia/Kolkata');
                @endphp
                <article class="market-panel flex min-w-0 flex-col p-5 transition-colors hover:border-amber-400/30">
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-wider {{ $categoryStyle }}">
                            <i class="fas {{ $icon }}"></i>{{ $nudgeCategory }}
                        </span>
                        <time class="text-right text-xs market-muted" datetime="{{ optional($observedAt)->toIso8601String() }}">
                            {{ optional($observedAt)->format('d M Y, h:i A') ?: optional($nudge->trading_date)->format('d M Y') }}{{ $observedAt ? ' IST' : '' }}
                        </time>
                    </div>

                    <div class="mt-5 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            @if($symbol)<span class="market-chip">{{ $symbol }}</span>@endif
                            @if($nudge->equity?->sector)<span class="market-chip">{{ $nudge->equity->sector }}</span>@endif
                            @if($nudge->equity?->market_cap_category)<span class="market-chip">{{ $nudge->equity->market_cap_category }}</span>@endif
                            <span class="font-mono text-slate-500">{{ $nudge->index_code ?: $nudge->isin }}</span>
                            <span class="text-slate-600">{{ $nudge->exchange }}</span>
                        </div>
                        @if($nudge->equity?->company_name || $nudge->index?->index_name)
                            <p class="mt-3 truncate text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $nudge->equity?->company_name ?: $nudge->index?->index_name }}</p>
                        @endif
                        <h2 class="mt-1.5 text-lg font-extrabold leading-snug text-white">{{ $nudge->title }}</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-300">{{ $nudge->message }}</p>
                    </div>

                    <div class="mt-auto flex items-end justify-between gap-3 border-t border-white/[.07] pt-5">
                        <span class="text-xs capitalize market-muted">{{ str_replace('_', ' ', $nudge->rule) }}</span>
                        @if($nudge->observed_price !== null)
                            <span class="text-lg font-black text-white">&#8377;{{ number_format((float) $nudge->observed_price, 2) }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="market-panel p-12 text-center market-muted md:col-span-2 xl:col-span-3">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white/5 text-slate-500"><i class="far fa-bell text-xl"></i></span>
                    <h2 class="mt-4 font-bold text-white">No nudges found</h2>
                    <p class="mt-1 text-sm">Try another category or search after the next market data sync.</p>
                </div>
            @endforelse
        </section>

        <div class="market-pagination mt-7">{{ $nudges->links() }}</div>
    </main>
</div>
@endsection
