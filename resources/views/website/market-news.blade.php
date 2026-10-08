@extends('layouts.public')
@section('title', 'Latest Financial News - SetuGeo Markets')
@section('meta_description', 'Read the latest Indian company and financial market news in a clear, fast news feed.')
@section('market_heading', 'Market news')
@section('market_subheading', 'The latest verified company and market stories, with concise previews and full article pages.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-5 sm:py-8">
    <div class="mx-auto max-w-6xl">
    <form method="GET" class="market-panel p-4 flex flex-col sm:flex-row gap-3 mb-7"><input class="market-input flex-1" type="search" name="search" value="{{ request('search') }}" placeholder="Search news by title, summary or symbol" aria-label="Search news"><button class="market-button sm:w-auto" type="submit"><i class="fas fa-search mr-2"></i>Search news</button></form>
    <div class="flex items-center justify-between mb-5"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($news->total()) }}</strong> stories</p><span class="text-xs market-muted">Newest first</span></div>
    <div class="space-y-4">
        @forelse($news as $story)
            <article class="market-panel overflow-hidden sm:grid sm:grid-cols-[200px_minmax(0,1fr)] sm:items-stretch">
                <div class="h-40 overflow-hidden border-b border-white/[.07] bg-slate-900 sm:h-auto sm:min-h-[205px] sm:border-b-0 sm:border-r">
                    @if($story->thumbnail && filter_var($story->thumbnail, FILTER_VALIDATE_URL))
                        <img src="{{ $story->thumbnail }}" alt="News image for {{ $story->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover" onerror="this.parentElement.innerHTML='<div class=&quot;grid h-full place-items-center text-slate-700&quot;><i class=&quot;far fa-newspaper text-3xl&quot;></i></div>'">
                    @else
                        <div class="grid h-full place-items-center bg-gradient-to-br from-slate-900 to-slate-950 text-slate-700"><i class="far fa-newspaper text-3xl"></i></div>
                    @endif
                </div>
                <div class="p-5 sm:p-6 min-w-0 flex flex-col">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-3"><span class="market-chip">{{ $story->symbol ?: 'Market' }}</span><time class="text-xs market-muted" datetime="{{ optional($story->published_at)->toIso8601String() }}">{{ optional($story->published_at)->format('d M Y') ?: 'Recently' }} &middot; {{ optional($story->published_at)->format('h:i A') }}</time></div>
                    <h2 class="market-clamp-2 font-extrabold text-lg sm:text-xl text-white leading-snug"><a href="{{ route('market.news.show', $story) }}" class="hover:text-amber-300 transition-colors">{{ $story->title }}</a></h2>
                    @if($story->summary)<p class="market-clamp-2 text-sm text-slate-300 leading-6 mt-3 break-words">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags($story->summary))), 200) }}</p>@else<p class="market-clamp-2 text-sm italic leading-6 market-muted mt-3">No additional description is available for this story.</p>@endif
                    <div class="mt-auto pt-4"><a href="{{ route('market.news.show', $story) }}" class="market-button inline-flex w-auto items-center px-4 py-2 text-sm" aria-label="Read full story: {{ $story->title }}">View details <i class="fas fa-arrow-right ml-2 text-xs"></i></a></div>
                </div>
            </article>
        @empty<div class="market-panel p-12 text-center market-muted"><i class="far fa-newspaper text-3xl mb-3 block"></i>No news stories match your search.</div>@endforelse
    </div><div class="market-pagination mt-7">{{ $news->links() }}</div>
    </div>
</div></div>
@endsection
