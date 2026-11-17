@extends('layouts.public')
@section('title', 'Technology News'.(request('page', 1) > 1 ? ' — Page '.request('page') : '').' - SetuGeo')
@section('meta_description', 'Read the latest verified technology stories with concise previews and full SetuGeo article pages.')
@section('robots', count(request()->except('page')) ? 'noindex, follow' : 'index, follow')
@section('canonical_url', route('technology-news.index').(request('page', 1) > 1 ? '?page='.request('page') : ''))
@section('content')
<div class="market-shell">@include('website.partials.technology-news-nav')
<div class="market-wrap py-5 sm:py-8"><div class="mx-auto max-w-6xl">
    <form method="GET" class="market-panel p-4 mb-7" x-data="{ submitting: false }" @submit="submitting = true" :aria-busy="submitting">
        <div class="flex flex-col sm:flex-row gap-3"><input class="market-input flex-1" type="search" name="search" value="{{ request('search', request('q')) }}" maxlength="150" placeholder="Search news by title, summary or topic" aria-label="Search news"><button class="market-button sm:w-auto disabled:opacity-60" type="submit" :disabled="submitting"><i class="fas fa-search mr-2" aria-hidden="true"></i><span x-text="submitting ? 'Searching…' : 'Search news'">Search news</span></button></div>
        <details class="mt-4" @if(request('category') || request('source') || request('from') || request('to')) open @endif><summary class="cursor-pointer text-sm font-semibold text-slate-300">Filter by category, source or date</summary><div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-xs market-muted">Category<select name="category" class="market-input mt-2"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="text-xs market-muted">Source<select name="source" class="market-input mt-2"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source->id }}" @selected(request('source') == $source->id)>{{ $source->name }}</option>@endforeach</select></label>
            <label class="text-xs market-muted">From (UTC)<input type="date" name="from" value="{{ request('from') }}" class="market-input mt-2"></label><label class="text-xs market-muted">To (UTC)<input type="date" name="to" value="{{ request('to') }}" class="market-input mt-2"></label>
        </div></details>
        @if(count(request()->except('page')))<a href="{{ route('technology-news.index') }}" class="mt-4 inline-block text-sm text-amber-400 underline underline-offset-4">Clear filters</a>@endif
    </form>
    @if($errors->any())<div role="alert" class="market-panel mb-5 p-4 text-red-300">{{ $errors->first() }}</div>@endif
    <div class="flex items-center justify-between mb-5"><p class="text-sm market-muted"><strong class="text-white">{{ number_format($news->total()) }}</strong> stories</p><span class="text-xs market-muted">Newest first</span></div>
    <div class="space-y-4">
        @forelse($news as $story)
            <article class="market-panel overflow-hidden sm:grid sm:grid-cols-[200px_minmax(0,1fr)] sm:items-stretch">
                <div class="h-40 overflow-hidden border-b border-white/[.07] bg-slate-900 sm:h-auto sm:min-h-[205px] sm:border-b-0 sm:border-r"><div class="grid h-full place-items-center bg-slate-950 text-slate-700"><i class="far fa-newspaper text-3xl" aria-hidden="true"></i></div></div>
                <div class="p-5 sm:p-6 min-w-0 flex flex-col">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 mb-3"><span class="market-chip">{{ $story->category->name }}</span><time class="text-xs market-muted" datetime="{{ $story->published_at->toIso8601String() }}">{{ $story->published_at->copy()->timezone('Asia/Kolkata')->format('d M Y') }} &middot; {{ $story->published_at->copy()->timezone('Asia/Kolkata')->format('h:i A') }} IST</time></div>
                    <h2 class="market-clamp-2 font-extrabold text-lg sm:text-xl text-white leading-snug"><a href="{{ route('technology-news.show', $story->slug) }}" class="hover:text-amber-300 transition-colors">{{ $story->ai_title }}</a></h2>
                    <p class="market-clamp-2 text-sm text-slate-300 leading-6 mt-3 break-words">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', trim(strip_tags((string) $story->ai_summary))), 200) }}</p>
                    <p class="mt-3 text-xs market-muted">Adapted from {{ $story->publisher_name }}</p>
                    <div class="mt-auto pt-4"><a href="{{ route('technology-news.show', $story->slug) }}" class="market-button inline-flex w-auto items-center px-4 py-2 text-sm" aria-label="Read full story: {{ $story->ai_title }}">View details <i class="fas fa-arrow-right ml-2 text-xs" aria-hidden="true"></i></a></div>
                </div>
            </article>
        @empty<div class="market-panel p-12 text-center market-muted"><i class="far fa-newspaper text-3xl mb-3 block" aria-hidden="true"></i>No news stories match your search. New stories appear after AI verification.</div>@endforelse
    </div><div class="market-pagination mt-7">{{ $news->links() }}</div>
</div></div></div>
@endsection
