@extends('layouts.public')
@section('title', 'Latest Financial News - SetuGeo Markets')
@section('meta_description', 'Read the latest Indian company and financial market news in a clear, fast news feed.')
@section('market_heading', 'Market news')
@section('market_subheading', 'The latest company and market stories, with complete descriptions available directly on this page.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-6 sm:py-8">
    <form method="GET" class="market-panel p-4 flex flex-col sm:flex-row gap-3 mb-7"><input class="market-input flex-1" type="search" name="search" value="{{ request('search') }}" placeholder="Search news by title, summary or symbol" aria-label="Search news"><button class="market-button sm:w-auto" type="submit"><i class="fas fa-search mr-2"></i>Search news</button></form>
    <div class="flex items-center justify-between mb-5"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($news->total()) }}</strong> stories</p><span class="text-xs market-muted">Newest first</span></div>
    <div class="space-y-4 max-w-5xl">
        @forelse($news as $story)
            <article class="market-panel overflow-hidden sm:grid {{ $story->thumbnail && filter_var($story->thumbnail, FILTER_VALIDATE_URL) ? 'sm:grid-cols-[220px_minmax(0,1fr)]' : '' }}">
                @if($story->thumbnail && filter_var($story->thumbnail, FILTER_VALIDATE_URL))<img src="{{ $story->thumbnail }}" alt="" loading="lazy" decoding="async" class="w-full h-44 sm:h-full sm:min-h-[220px] object-cover bg-slate-900" onerror="this.remove()">@endif
                <div class="p-5 sm:p-6 min-w-0">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-3"><span class="market-chip">{{ $story->symbol ?: 'Market' }}</span><time class="text-xs market-muted" datetime="{{ optional($story->published_at)->toIso8601String() }}">{{ optional($story->published_at)->format('d M Y · h:i A') ?: 'Recently' }}</time></div>
                    <h2 class="font-extrabold text-lg sm:text-xl text-white leading-snug">{{ $story->title }}</h2>
                    @if($story->summary)<div class="text-sm text-slate-300 leading-7 mt-3 whitespace-pre-line break-words">{{ trim(strip_tags($story->summary)) }}</div>@else<p class="text-sm italic market-muted mt-3">No additional description is available for this story.</p>@endif
                </div>
            </article>
        @empty<div class="market-panel p-12 text-center market-muted"><i class="far fa-newspaper text-3xl mb-3 block"></i>No news stories match your search.</div>@endforelse
    </div><div class="market-pagination mt-7">{{ $news->links() }}</div>
</div></div>
@endsection
