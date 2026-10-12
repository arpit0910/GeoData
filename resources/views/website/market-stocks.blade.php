@extends('layouts.public')
@section('title', 'Indian Stocks - SetuGeo Markets')
@section('meta_description', 'Browse and search NSE and BSE stocks with latest prices, daily ranges and trading volume.')
@section('market_heading', 'Indian stocks')
@section('market_subheading', 'Search listed instruments and scan the latest available price, movement and volume data.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-5 sm:py-8">
    <form method="GET" class="market-panel mb-5 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
        <input class="market-input sm:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Company, symbol or ISIN" aria-label="Search stocks">
        <select class="market-input" name="instrument_type" aria-label="Instrument type"><option value="stocks" @selected(request('instrument_type', 'stocks') === 'stocks')>Stocks</option><option value="bonds" @selected(request('instrument_type') === 'bonds')>Bonds</option><option value="all" @selected(request('instrument_type') === 'all')>All instruments</option></select>
        <select class="market-input" name="filter" aria-label="Exchange or price status"><option value="all">All exchanges</option><option value="live" @selected(request('filter') === 'live')>Live prices</option><option value="nse" @selected(request('filter') === 'nse')>NSE</option><option value="bse" @selected(request('filter') === 'bse')>BSE</option></select>
        <select class="market-input" name="sector" aria-label="Sector"><option value="">All sectors</option>@foreach($sectors as $sector)<option value="{{ $sector }}" @selected(request('sector') === $sector)>{{ $sector }}</option>@endforeach</select>
        <select class="market-input" name="stock_category" aria-label="Stock category"><option value="">All stock categories</option>@foreach($stockCategories as $stockCategory)<option value="{{ $stockCategory }}" @selected(request('stock_category') === $stockCategory)>{{ $stockCategory }}</option>@endforeach</select>
        <select class="market-input" name="sort" aria-label="Sort stocks"><option value="volume_desc" @selected(request('sort', 'volume_desc') === 'volume_desc')>Most active</option><option value="gainers" @selected(request('sort') === 'gainers')>Top gainers</option><option value="losers" @selected(request('sort') === 'losers')>Top losers</option><option value="name_asc" @selected(request('sort') === 'name_asc')>Name A&ndash;Z</option><option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z&ndash;A</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Highest price</option><option value="price_asc" @selected(request('sort') === 'price_asc')>Lowest price</option></select>
        <div class="flex min-w-0 items-center gap-3"><button class="market-button min-w-0 flex-1" type="submit"><i class="fas fa-filter mr-2"></i>Apply</button><a href="{{ route('market.stocks') }}" class="shrink-0 text-xs font-bold text-slate-400 hover:text-white">Reset</a></div>
    </form>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($stocks->total()) }}</strong> instruments found</p><p class="text-xs market-muted"><span class="mr-1 inline-block h-2 w-2 rounded-full {{ $marketStatus['is_open'] ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>{{ $marketStatus['status'] }}</p></div>
    <div class="market-panel overflow-hidden sm:overflow-x-auto"><table class="market-table market-mobile-cards">
        <thead><tr><th>Company</th><th>Exchange</th><th class="text-right">Latest price</th><th class="text-right">Change</th><th class="text-right">Latest range</th><th class="text-right">Volume</th></tr></thead>
        <tbody>@forelse($stocks as $stock)
            @php
                $isLive = $stock->live_price !== null;
                $price = (float) ($isLive ? $stock->live_price : $stock->latest_close);
                $previous = (float) ($isLive ? $stock->live_previous_close : $stock->prev_close);
                $change = $isLive && $stock->live_change_percent !== null
                    ? (float) $stock->live_change_percent
                    : ($previous > 0 && $price > 0 ? (($price - $previous) / $previous) * 100 : null);
                $volume = $isLive ? $stock->live_volume : $stock->day_volume;
                $rangeDate = $stock->price_date ? \Carbon\Carbon::parse($stock->price_date)->format('d M') : null;
            @endphp
            <tr><td data-label="Company"><div class="flex flex-wrap items-center gap-1.5 font-bold text-white">{{ $stock->nse_symbol ?: ($stock->bse_symbol ?: $stock->isin) }} <span class="market-chip">{{ $stock->series ?: 'EQ' }}</span>@if($stock->market_cap_category)<span class="market-chip">{{ $stock->market_cap_category }}</span>@endif</div><div class="mt-1 max-w-sm truncate text-sm market-muted">{{ $stock->company_name }}</div>@if($stock->sector)<div class="mt-1 text-xs text-slate-500">{{ $stock->sector }}</div>@endif</td>
                <td data-label="Exchange"><span class="market-chip">{{ $stock->nse_symbol ? 'NSE' : 'BSE' }}</span></td>
                <td data-label="Latest price" class="text-right font-mono font-bold text-white">{!! $price > 0 ? '&#8377;'.number_format($price, 2) : '&mdash;' !!}</td>
                <td data-label="Change" class="text-right font-mono font-bold {{ $change === null ? 'text-slate-500' : ($change >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{!! $change === null ? '&mdash;' : (($change >= 0 ? '+' : '').number_format($change, 2).'%') !!}</td>
                <td data-label="Latest range" class="text-right text-sm"><div><span class="text-emerald-400">{!! $stock->day_high ? number_format($stock->day_high, 2) : '&mdash;' !!}</span><span class="market-muted"> / </span><span class="text-rose-400">{!! $stock->day_low ? number_format($stock->day_low, 2) : '&mdash;' !!}</span></div>@if($rangeDate)<div class="mt-1 text-[10px] text-slate-500">{{ $rangeDate }}</div>@endif</td>
                <td data-label="Volume" class="text-right font-mono text-sm">{!! $volume ? number_format($volume) : '&mdash;' !!}</td></tr>
        @empty<tr><td colspan="6" class="py-12 text-center market-muted">No stocks match these filters.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="market-pagination mt-6">{{ $stocks->links() }}</div>
</div></div>
@endsection
