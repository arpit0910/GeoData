@extends('layouts.public')
@section('title', 'Indian Mutual Funds - SetuGeo Markets')
@section('meta_description', 'Search Indian mutual fund schemes and compare their latest NAV and available returns.')
@section('market_heading', 'Mutual funds')
@section('market_subheading', 'Find AMFI schemes and compare the latest available NAV and return periods.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-5 sm:py-8">
    <form method="GET" class="market-panel mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
        <input class="market-input sm:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Scheme, AMC, code or ISIN" aria-label="Search mutual funds">
        <select class="market-input" name="category" aria-label="Fund category"><option value="all">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select>
        <select class="market-input" name="amc" aria-label="Asset management company"><option value="all">All fund houses</option>@foreach($amcs as $amc)<option value="{{ $amc }}" @selected(request('amc') === $amc)>{{ $amc }}</option>@endforeach</select>
        <select class="market-input" name="sort" aria-label="Sort mutual funds"><option value="popular" @selected(request('sort', 'popular') === 'popular')>Popular first</option><option value="name_asc" @selected(request('sort') === 'name_asc')>Name A&ndash;Z</option><option value="name_desc" @selected(request('sort') === 'name_desc')>Name Z&ndash;A</option><option value="nav_desc" @selected(request('sort') === 'nav_desc')>Highest NAV</option><option value="nav_asc" @selected(request('sort') === 'nav_asc')>Lowest NAV</option><option value="chg_1d_desc" @selected(request('sort') === 'chg_1d_desc')>Best 1D return</option><option value="chg_1y_desc" @selected(request('sort') === 'chg_1y_desc')>Best 1Y return</option><option value="chg_3y_desc" @selected(request('sort') === 'chg_3y_desc')>Best 3Y return</option></select>
        <div class="flex min-w-0 items-center gap-3"><button class="market-button min-w-0 flex-1" type="submit"><i class="fas fa-filter mr-2"></i>Apply</button><a href="{{ route('market.mutual-funds') }}" class="shrink-0 text-xs font-bold text-slate-400 hover:text-white">Reset</a></div>
    </form>
    <div class="mb-4 flex justify-between"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($funds->total()) }}</strong> schemes found</p></div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($funds as $fund)
            <article class="market-panel flex min-w-0 flex-col p-5">
                <div class="flex min-w-0 items-start justify-between gap-3"><span class="market-chip shrink-0">{{ $fund->category ?: 'Fund' }}</span><span class="shrink-0 text-xs market-muted">{{ $fund->nav_date ? \Carbon\Carbon::parse($fund->nav_date)->format('d M Y') : 'NAV unavailable' }}</span></div>
                <h2 class="mt-4 min-h-[2.75rem] break-words font-bold leading-snug text-white">{{ $fund->scheme_name }}</h2><p class="mt-2 truncate text-xs market-muted" title="{{ $fund->amc_name }}">{{ $fund->amc_name }}</p>
                <div class="mt-5 flex items-end justify-between border-t border-white/10 pt-4"><div><span class="text-xs market-muted">Latest NAV</span><div class="mt-1 text-xl font-extrabold text-white">{!! $fund->nav ? '&#8377;'.number_format($fund->nav, 4) : '&mdash;' !!}</div></div><div class="text-right"><span class="text-xs market-muted">1 year</span><div class="mt-1 font-bold {{ $fund->chg_1y === null ? 'text-slate-500' : ($fund->chg_1y >= 0 ? 'text-emerald-400' : 'text-rose-400') }}">{!! $fund->chg_1y === null ? '&mdash;' : (($fund->chg_1y >= 0 ? '+' : '').number_format($fund->chg_1y, 2).'%') !!}</div></div></div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-lg bg-white/[.03] p-2"><div class="text-[10px] market-muted">1D</div><div class="mt-1 text-xs font-bold">{!! $fund->chg_1d === null ? '&mdash;' : number_format($fund->chg_1d, 2).'%' !!}</div></div><div class="rounded-lg bg-white/[.03] p-2"><div class="text-[10px] market-muted">1M</div><div class="mt-1 text-xs font-bold">{!! $fund->chg_1m === null ? '&mdash;' : number_format($fund->chg_1m, 2).'%' !!}</div></div><div class="rounded-lg bg-white/[.03] p-2"><div class="text-[10px] market-muted">3Y</div><div class="mt-1 text-xs font-bold">{!! $fund->chg_3y === null ? '&mdash;' : number_format($fund->chg_3y, 2).'%' !!}</div></div></div>
            </article>
        @empty<div class="market-panel p-12 text-center market-muted md:col-span-2 xl:col-span-3">No mutual funds match these filters.</div>@endforelse
    </div><div class="market-pagination mt-6">{{ $funds->links() }}</div>
</div></div>
@endsection
