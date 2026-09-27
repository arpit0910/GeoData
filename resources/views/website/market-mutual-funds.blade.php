@extends('layouts.public')
@section('title', 'Indian Mutual Funds - SetuGeo Markets')
@section('meta_description', 'Search Indian mutual fund schemes and compare their latest NAV and available returns.')
@section('market_heading', 'Mutual funds')
@section('market_subheading', 'Find AMFI schemes and compare the latest available NAV and return periods.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-8">
    <form method="GET" class="market-panel p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <input class="market-input lg:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Scheme, AMC, code or ISIN" aria-label="Search mutual funds">
        <select class="market-input" name="category" aria-label="Fund category"><option value="all">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
        <button class="market-button" type="submit"><i class="fas fa-search mr-2"></i>Find funds</button>
    </form>
    <div class="flex justify-between mb-4"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($funds->total()) }}</strong> schemes found</p></div>
    <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($funds as $fund)
            <article class="market-panel p-5">
                <div class="flex items-start justify-between gap-3"><span class="market-chip">{{ $fund->category ?: 'Fund' }}</span><span class="text-xs market-muted">{{ $fund->nav_date ? \Carbon\Carbon::parse($fund->nav_date)->format('d M Y') : 'NAV unavailable' }}</span></div>
                <h2 class="font-bold text-white leading-snug mt-4 min-h-[2.75rem]">{{ $fund->scheme_name }}</h2><p class="text-xs market-muted mt-2">{{ $fund->amc_name }}</p>
                <div class="flex items-end justify-between border-t border-white/10 mt-5 pt-4"><div><span class="text-xs market-muted">Latest NAV</span><div class="text-xl font-extrabold text-white mt-1">{{ $fund->nav ? '₹'.number_format($fund->nav, 4) : '—' }}</div></div><div class="text-right"><span class="text-xs market-muted">1 year</span><div class="font-bold mt-1 {{ $fund->chg_1y === null ? 'text-slate-500' : ($fund->chg_1y >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{{ $fund->chg_1y === null ? '—' : (($fund->chg_1y >= 0 ? '+' : '').number_format($fund->chg_1y, 2).'%') }}</div></div></div>
                <div class="grid grid-cols-3 gap-2 mt-4 text-center"><div class="bg-white/[.03] rounded-lg p-2"><div class="text-[10px] market-muted">1D</div><div class="text-xs font-bold mt-1">{{ $fund->chg_1d === null ? '—' : number_format($fund->chg_1d, 2).'%' }}</div></div><div class="bg-white/[.03] rounded-lg p-2"><div class="text-[10px] market-muted">1M</div><div class="text-xs font-bold mt-1">{{ $fund->chg_1m === null ? '—' : number_format($fund->chg_1m, 2).'%' }}</div></div><div class="bg-white/[.03] rounded-lg p-2"><div class="text-[10px] market-muted">3Y</div><div class="text-xs font-bold mt-1">{{ $fund->chg_3y === null ? '—' : number_format($fund->chg_3y, 2).'%' }}</div></div></div>
            </article>
        @empty<div class="market-panel p-12 text-center market-muted md:col-span-2 xl:col-span-3">No mutual funds match these filters.</div>@endforelse
    </div><div class="market-pagination mt-6">{{ $funds->links() }}</div>
</div></div>
@endsection
