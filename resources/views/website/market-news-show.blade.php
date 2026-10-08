@extends('layouts.public')
@section('title', $marketNews->title.' - SetuGeo Markets')
@section('meta_description', \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', strip_tags((string) $marketNews->summary)), 155))
@section('market_heading', 'Market news')
@section('market_subheading', 'A verified company and financial-market news report.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-news-detail-wrap py-5 sm:py-8 lg:py-10">
    <a href="{{ route('market.news') }}" class="mb-4 sm:mb-6 inline-flex items-center text-sm font-bold text-amber-400 hover:text-amber-300"><i class="fas fa-arrow-left mr-2"></i>Back to market news</a>
    <article class="market-panel overflow-hidden">
        @if($marketNews->thumbnail && filter_var($marketNews->thumbnail, FILTER_VALIDATE_URL))
            <div class="aspect-[16/9] max-h-[430px] overflow-hidden border-b border-white/[.07] bg-slate-900"><img src="{{ $marketNews->thumbnail }}" alt="News image for {{ $marketNews->title }}" class="h-full w-full object-cover" decoding="async" onerror="this.parentElement.remove()"></div>
        @endif
        <div class="px-5 py-6 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
            <div class="mx-auto max-w-3xl">
                <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 sm:mb-5">
                    <span class="market-chip">{{ $marketNews->symbol ?: 'Market' }}</span>
                    <time class="text-xs market-muted" datetime="{{ optional($marketNews->published_at)->toIso8601String() }}">{{ optional($marketNews->published_at)->format('d M Y') ?: 'Recently' }} &middot; {{ optional($marketNews->published_at)->format('h:i A') }}</time>
                </div>
                <h1 class="break-words text-2xl font-black leading-tight text-white sm:text-3xl lg:text-4xl">{{ $marketNews->title }}</h1>
                @php
                    $articleParagraphs = array_values(array_filter(
                        preg_split('/(?:\r?\n){2,}/', trim(strip_tags((string) $marketNews->summary))) ?: [],
                        static fn ($paragraph) => trim($paragraph) !== ''
                    ));
                @endphp
                <div class="mt-6 space-y-5 text-[15px] leading-7 text-slate-300 sm:mt-8 sm:space-y-6 sm:text-base sm:leading-8">
                    @forelse($articleParagraphs as $paragraph)
                        <p class="break-words">{{ preg_replace('/\s+/u', ' ', trim($paragraph)) }}</p>
                    @empty
                        <p class="italic market-muted">No additional description is available for this story.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </article>
</div></div>
@endsection
