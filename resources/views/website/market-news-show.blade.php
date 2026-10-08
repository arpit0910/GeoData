@extends('layouts.public')
@section('title', $marketNews->title.' - SetuGeo Markets')
@section('meta_description', \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', strip_tags((string) $marketNews->summary)), 155))
@section('market_heading', 'Market news')
@section('market_subheading', 'A verified company and financial-market news report.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-6 sm:py-8 max-w-5xl">
    <a href="{{ route('market.news') }}" class="inline-flex items-center text-sm font-bold text-amber-400 hover:text-amber-300 mb-5"><i class="fas fa-arrow-left mr-2"></i>Back to market news</a>
    <article class="market-panel overflow-hidden">
        @if($marketNews->thumbnail && filter_var($marketNews->thumbnail, FILTER_VALIDATE_URL))
            <img src="{{ $marketNews->thumbnail }}" alt="" class="w-full max-h-[420px] object-cover bg-slate-900" decoding="async" onerror="this.remove()">
        @endif
        <div class="p-5 sm:p-8 lg:p-10">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-4">
                <span class="market-chip">{{ $marketNews->symbol ?: 'Market' }}</span>
                <time class="text-xs market-muted" datetime="{{ optional($marketNews->published_at)->toIso8601String() }}">{{ optional($marketNews->published_at)->format('d M Y') ?: 'Recently' }} &middot; {{ optional($marketNews->published_at)->format('h:i A') }}</time>
            </div>
            <h1 class="text-2xl sm:text-4xl font-black text-white leading-tight">{{ $marketNews->title }}</h1>
            <div class="mt-7 whitespace-pre-line break-words text-base leading-8 text-slate-300">{{ trim(strip_tags((string) $marketNews->summary)) }}</div>
        </div>
    </article>
</div></div>
@endsection
