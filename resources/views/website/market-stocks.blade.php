@extends('layouts.public')
@section('title', 'Indian Stocks - SetuGeo Markets')
@section('meta_description', 'Browse and search NSE and BSE stocks with latest prices, daily ranges and trading volume.')
@section('market_heading', 'Indian stocks')
@section('market_subheading', 'Search listed instruments and scan the latest available price, movement and volume data.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-8">
    <form method="GET" class="market-panel p-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <input class="market-input lg:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Company, symbol or ISIN" aria-label="Search stocks">
        <select class="market-input" name="instrument_type" aria-label="Instrument type"><option value="stocks" @selected(request('instrument_type', 'stocks') === 'stocks')>Stocks</option><option value="bonds" @selected(request('instrument_type') === 'bonds')>Bonds</option><option value="all" @selected(request('instrument_type') === 'all')>All instruments</option></select>
        <select class="market-input" name="sort" aria-label="Sort stocks"><option value="volume_desc">Most active</option><option value="gainers" @selected(request('sort') === 'gainers')>Top gainers</option><option value="losers" @selected(request('sort') === 'losers')>Top losers</option><option value="name_asc" @selected(request('sort') === 'name_asc')>Name A–Z</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Highest price</option></select>
        <button class="market-button" type="submit"><i class="fas fa-search mr-2"></i>Apply</button>
    </form>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($stocks->total()) }}</strong> instruments found</p><p class="text-xs market-muted"><span class="inline-block w-2 h-2 rounded-full {{ $marketStatus['is_open'] ? 'bg-emerald-400' : 'bg-slate-500' }} mr-1"></span>{{ $marketStatus['status'] }}</p></div>
    <div class="market-panel overflow-x-auto"><table class="market-table">
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
            <tr><td><div class="font-bold text-white">{{ $stock->nse_symbol ?: ($stock->bse_symbol ?: $stock->isin) }} <span class="market-chip ml-1">{{ $stock->series ?: 'EQ' }}</span></div><div class="text-sm market-muted mt-1 max-w-sm truncate">{{ $stock->company_name }}</div></td>
                <td><span class="market-chip">{{ $stock->nse_symbol ? 'NSE' : 'BSE' }}</span></td>
                <td class="text-right font-mono font-bold text-white">{{ $price > 0 ? '₹'.number_format($price, 2) : '—' }}</td>
                <td class="text-right font-mono font-bold {{ $change === null ? 'text-slate-500' : ($change >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{{ $change === null ? '—' : (($change >= 0 ? '+' : '').number_format($change, 2).'%') }}</td>
                <td class="text-right text-sm"><div><span class="text-emerald-400">{{ $stock->day_high ? number_format($stock->day_high, 2) : '—' }}</span><span class="market-muted"> / </span><span class="text-rose-400">{{ $stock->day_low ? number_format($stock->day_low, 2) : '—' }}</span></div>@if($rangeDate)<div class="mt-1 text-[10px] text-slate-500">{{ $rangeDate }}</div>@endif</td>
                <td class="text-right font-mono text-sm">{{ $volume ? number_format($volume) : '—' }}</td></tr>
        @empty<tr><td colspan="6" class="text-center market-muted py-12">No stocks match these filters.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="market-pagination mt-6">{{ $stocks->links() }}</div>
</div></div>
@endsection
