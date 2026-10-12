@extends('layouts.public')
@section('title', 'Indian Market Indices - SetuGeo Markets')
@section('meta_description', 'Browse synced NSE and BSE index prices, market signals and recent performance.')
@section('market_heading', 'Market indices')
@section('market_subheading', 'Synced NSE and BSE benchmarks with live prices and directional signals.')

@section('content')
<div class="market-shell">
    @include('website.partials.market-nav')

    <main class="market-wrap py-5 sm:py-8">
        <form method="GET" class="market-panel mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_150px_190px_150px_auto]">
            <input class="market-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search index name or code" aria-label="Search indices">
            <select class="market-input" name="exchange" aria-label="Exchange">
                <option value="">All exchanges</option>
                @foreach(['NSE', 'BSE'] as $exchange)<option value="{{ $exchange }}" @selected(($filters['exchange'] ?? '') === $exchange)>{{ $exchange }}</option>@endforeach
            </select>
            <select class="market-input" name="category" aria-label="Index category">
                <option value="">All categories</option>
                @foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach
            </select>
            <select class="market-input" name="signal" aria-label="Market signal">
                <option value="">All signals</option>
                <option value="growth" @selected(($filters['signal'] ?? '') === 'growth')>Growth</option>
                <option value="fall" @selected(($filters['signal'] ?? '') === 'fall')>Fall</option>
                <option value="flat" @selected(($filters['signal'] ?? '') === 'flat')>Flat</option>
            </select>
            <button class="market-button" type="submit"><i class="fas fa-filter mr-2"></i>Filter</button>
        </form>

        <div class="mb-5 flex items-center justify-between gap-3">
            <p class="text-sm market-muted"><strong class="text-white">{{ number_format($indices->total()) }}</strong> synced indices</p>
            @if(request()->query())<a href="{{ route('market.indices') }}" class="text-sm font-bold text-amber-400 hover:text-amber-300">Clear filters</a>@endif
        </div>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="Synced market indices">
            @forelse($indices as $index)
                @php
                    $quote = $index->quote;
                    $change = $quote->change_percent === null ? null : (float) $quote->change_percent;
                    $signal = $change === null || $change == 0 ? 'Flat' : ($change > 0 ? 'Growth' : 'Fall');
                    $quotedAt = $quote->quoted_at?->copy()->timezone('Asia/Kolkata');
                    $isLive = $quote->quoted_at && $quote->quoted_at->gte(now()->subMinutes(15));
                @endphp
                <a href="{{ route('market.indices.show', $index) }}" class="market-panel flex min-w-0 flex-col p-5 transition-all hover:-translate-y-0.5 hover:border-amber-400/40">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-extrabold text-white">{{ $index->index_name }}</h2>
                            <p class="mt-1 font-mono text-xs market-muted">{{ $index->index_code }} &middot; {{ $index->exchange }}</p>
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1.5">
                            <span class="market-chip"><span class="mr-1.5 h-1.5 w-1.5 rounded-full {{ $isLive ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' }}"></span>{{ $isLive ? 'Live' : 'Stale' }}</span>
                            <span class="rounded-full border px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $signal === 'Growth' ? 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300' : ($signal === 'Fall' ? 'border-rose-400/25 bg-rose-400/10 text-rose-300' : 'border-slate-400/25 bg-slate-400/10 text-slate-300') }}">{{ $signal }} signal</span>
                        </div>
                    </div>
                    <div class="mt-6 flex items-end justify-between gap-3">
                        <span class="text-2xl font-black text-white">{{ number_format((float) $quote->price, 2) }}</span>
                        <span class="font-mono text-sm font-bold {{ $change === null ? 'text-slate-500' : ($change >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{{ $change === null ? '—' : (($change >= 0 ? '+' : '').number_format($change, 2).'%') }}</span>
                    </div>
                    <div class="mt-4 flex items-center justify-between border-t border-white/[.07] pt-4 text-xs market-muted">
                        <span>{{ $index->category ?: 'Market index' }}</span>
                        <span>{{ $quotedAt ? $quotedAt->format('d M, h:i A').' IST' : 'Time unavailable' }}</span>
                    </div>
                </a>
            @empty
                <div class="market-panel p-12 text-center market-muted sm:col-span-2 lg:col-span-3">No synced indices match these filters.</div>
            @endforelse
        </section>

        <div class="market-pagination mt-7">{{ $indices->links() }}</div>
    </main>
</div>
@endsection
