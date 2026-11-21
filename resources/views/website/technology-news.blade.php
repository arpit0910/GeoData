@extends('layouts.public')
@section('title', 'Technology News'.(request('page', 1) > 1 ? ' — Page '.request('page') : '').' - SetuGeo')
@section('meta_description', 'Read the latest verified technology stories with concise previews and full SetuGeo article pages.')
@section('robots', count(request()->except('page')) ? 'noindex, follow' : 'index, follow')
@section('canonical_url', route('technology-news.index').(request('page', 1) > 1 ? '?page='.request('page') : ''))
@section('content')
<div class="market-shell">
    @include('website.partials.technology-news-nav')
    <div class="market-wrap">
        <div class="mx-auto max-w-5xl py-6 sm:py-8">
            @php
                $search = request('q', request('search'));
                $filterCount = (int) request()->filled('category') + (int) request()->filled('source')
                    + (int) (request()->filled('from') || request()->filled('to'));
                $hasFilters = filled($search) || $filterCount > 0;
            @endphp
            <form method="GET" action="{{ route('technology-news.index') }}" role="search" aria-label="Technology news"
                  class="market-panel mb-6 p-4 sm:p-5" x-data="{ submitting: false }"
                  @submit="submitting = true" @pageshow.window="submitting = false" :aria-busy="submitting">
                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-stretch gap-2 sm:gap-3">
                    <input class="market-input" type="search" name="q" value="{{ $search }}" maxlength="150"
                           placeholder="Search stories" aria-label="Search technology news">
                    <button class="market-button min-h-[44px] disabled:opacity-60" type="submit" :disabled="submitting">
                        <span x-text="submitting ? 'Searching…' : 'Search'">Search</span>
                    </button>
                </div>
                <details class="mt-3" @if($filterCount || $errors->any()) open @endif>
                    <summary class="min-h-[44px] w-fit cursor-pointer py-3 text-sm font-semibold text-slate-200">
                        {{ $filterCount ? "Filters ({$filterCount})" : 'Filters' }}
                    </summary>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="min-w-0 text-sm market-muted">Category
                            <select name="category" class="market-input mt-1">
                                <option value="">All categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="min-w-0 text-sm market-muted">Source
                            <select name="source" class="market-input mt-1">
                                <option value="">All sources</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}" @selected(request('source') == $source->id)>{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="min-w-0 text-sm market-muted">From (UTC)
                            <input type="date" name="from" value="{{ request('from') }}" class="market-input mt-1 [color-scheme:dark]">
                        </label>
                        <label class="min-w-0 text-sm market-muted">To (UTC)
                            <input type="date" name="to" value="{{ request('to') }}" class="market-input mt-1 [color-scheme:dark]">
                        </label>
                    </div>
                    <button class="market-button mt-4 min-h-[44px] disabled:opacity-60" type="submit" :disabled="submitting">Apply filters</button>
                </details>
            </form>

            @if($errors->any())
                <p role="alert" class="market-panel mb-5 p-4 text-red-300">{{ $errors->first() }}</p>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 pb-4">
                <h2 class="text-xl font-bold text-white">{{ $hasFilters ? 'Search results' : 'Latest stories' }}</h2>
                <div class="flex items-center gap-4 text-sm market-muted">
                    <span>{{ number_format($news->total()) }} {{ Str::plural('story', $news->total()) }}</span>
                    @if($hasFilters)
                        <a href="{{ route('technology-news.index') }}" class="inline-flex min-h-[44px] items-center text-amber-400 underline underline-offset-4">Clear filters</a>
                    @endif
                </div>
            </div>

            <div class="divide-y divide-white/10">
                @forelse($news as $story)
                    @php($publishedAt = $story->published_at->copy()->timezone('Asia/Kolkata'))
                    <article class="min-w-0 py-5 sm:py-6">
                        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                            <span class="font-semibold text-amber-300">{{ $story->category?->name ?? 'Technology' }}</span>
                            <time class="market-muted" datetime="{{ $publishedAt->toIso8601String() }}">{{ $publishedAt->format('j M Y, g:i A') }} IST</time>
                        </div>
                        <h3 class="break-words text-lg font-bold leading-snug text-white sm:text-xl">
                            <a href="{{ route('technology-news.show', $story->slug) }}" class="rounded-sm decoration-amber-400 underline-offset-4 hover:text-amber-300 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-400">
                                {{ $story->ai_title }}
                            </a>
                        </h3>
                        <p class="mt-2 max-w-[72ch] break-words text-sm leading-6 text-slate-300">{{ preg_replace('/\s+/u', ' ', trim(strip_tags((string) $story->ai_summary))) }}</p>
                        <p class="mt-2 text-xs market-muted">{{ $story->publisher_name }}</p>
                    </article>
                @empty
                    <div class="py-12">
                        <h3 class="text-lg font-bold text-white">{{ $hasFilters ? 'No matching stories' : 'No stories on this page' }}</h3>
                        <p class="mt-2 text-sm market-muted">{{ $hasFilters ? 'Try a different search or clear your filters.' : 'Browse the latest technology news.' }}</p>
                        <a href="{{ route('technology-news.index') }}" class="market-button mt-4 min-h-[44px]">{{ $hasFilters ? 'Clear filters' : 'Browse latest stories' }}</a>
                    </div>
                @endforelse
            </div>
            @if($news->hasPages())
                <div class="market-pagination mt-5 border-t border-white/10 pt-5">{{ $news->onEachSide(1)->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
