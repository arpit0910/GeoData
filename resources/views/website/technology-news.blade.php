@extends('layouts.public')
@section('title', 'Technology News'.(request('page', 1) > 1 ? ' — Page '.request('page') : '').' | SetuGeo')
@section('meta_description', 'Clear technology updates on AI, software and cybersecurity. AI-assisted summaries checked against attributed source material, with links to the publishers.')
@section('robots', count(request()->except('page')) ? 'noindex, follow' : 'index, follow')
@section('canonical_url', route('technology-news.index').(request('page', 1) > 1 ? '?page='.request('page') : ''))
@section('content')
<link rel="stylesheet" href="{{ asset('assets/css/technology-news.css') }}?v=2">
<div class="tech-news" id="technology-news"><div class="tech-container">
    <header class="tech-header"><div><h1>Technology News</h1><p>Keep up with the ideas, tools and companies shaping technology. Concise updates, checked against the source.</p></div><a href="#latest" class="tech-text-link">Explore latest <i class="fas fa-arrow-down" aria-hidden="true"></i></a></header>
    <nav class="tech-categories" aria-label="News categories">
        <a href="{{ route('technology-news.index', request()->except('category', 'page')) }}" @if(!request('category')) aria-current="page" @endif>All news</a>
        @foreach($categories as $category)<a href="{{ route('technology-news.index', array_merge(request()->except('page'), ['category' => $category->id])) }}" @if(request('category') == $category->id) aria-current="page" @endif>{{ $category->name }}</a>@endforeach
    </nav>
    <form method="GET" action="{{ route('technology-news.index') }}" class="tech-filters" x-data="{ submitting: false }" @submit="submitting = true" :aria-busy="submitting">
        @if(request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif
        <label class="tech-search">Search news<input type="search" name="q" value="{{ request('q') }}" maxlength="150" placeholder="AI, software, cybersecurity…"></label>
        <label>Source<select name="source"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source->id }}" @selected(request('source') == $source->id)>{{ $source->name }}</option>@endforeach</select></label>
        <details class="tech-date-filters" @if(request('from') || request('to')) open @endif><summary>Date range <i class="fas fa-chevron-down" aria-hidden="true"></i></summary><div><label>From (UTC)<input type="date" name="from" value="{{ request('from') }}"></label><label>To (UTC)<input type="date" name="to" value="{{ request('to') }}"></label></div></details>
        <button type="submit" class="tech-button" :disabled="submitting"><span x-text="submitting ? 'Searching…' : 'Search news'">Search news</span></button>
    </form>
    @if($errors->any())<div role="alert" class="tech-error"><strong>Check your filters.</strong> {{ $errors->first() }} <a href="{{ route('technology-news.index') }}">Reset filters</a></div>@endif
    @if(count(request()->except('page')))<div class="tech-filter-status"><span>Showing filtered results @if(request('q')) for “{{ request('q') }}”@endif</span><a href="{{ route('technology-news.index') }}">Clear all filters</a></div>@endif
    @if($featured && !count(request()->except('page')) && $news->currentPage() === 1)
        <aside class="tech-featured" aria-label="Editorial highlight"><p>Editorial highlight · {{ $featured->publisher_name }}</p><h2><a href="{{ $featured->original_url }}" rel="noopener noreferrer">{{ $featured->ai_title }}</a></h2><p>{{ $featured->ai_summary }}</p><a href="{{ $featured->original_url }}" class="tech-text-link" rel="noopener noreferrer">Read original <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></aside>
    @endif
    <div class="tech-columns">
        <section id="latest" aria-labelledby="latest-title" class="tech-feed">
            <div class="tech-section-heading"><h2 id="latest-title">Latest stories</h2><p>{{ number_format($news->total()) }} {{ $news->total() === 1 ? 'story' : 'stories' }} · Newest first</p></div>
            @forelse($news as $story)
                <article class="tech-story">
                    <div class="tech-story-meta"><a href="{{ route('technology-news.index', ['category' => $story->category_id]) }}" class="tech-category">{{ $story->category->name }}</a><span>{{ $story->publisher_name }}</span><time datetime="{{ $story->published_at->toIso8601String() }}">{{ $story->published_at->copy()->timezone('Asia/Kolkata')->format('d M Y, H:i') }} IST</time></div>
                    <h3><a href="{{ $story->original_url }}" rel="noopener noreferrer">{{ $story->ai_title }}</a></h3><p class="tech-summary">{{ $story->ai_summary }}</p>
                    <div class="tech-story-footer"><p>@if($story->author_name)By {{ $story->author_name }} · @endif Adapted from {{ $story->source->name }}</p><a class="tech-text-link" href="{{ $story->original_url }}" rel="noopener noreferrer" aria-label="Read original: {{ $story->ai_title }}">Read original <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i></a></div>
                    <a href="{{ $story->source->permission_reference }}" class="tech-licence" rel="noopener noreferrer">Source licence & attribution</a>
                </article>
            @empty
                <div class="tech-empty"><i class="far fa-newspaper" aria-hidden="true"></i><h3>{{ count(request()->except('page')) ? 'No stories match these filters' : 'New stories are on their way' }}</h3><p>{{ count(request()->except('page')) ? 'Try a different search, source or date range.' : 'Stories appear after rewriting and source verification are complete. Check back for the latest updates.' }}</p>@if(count(request()->except('page')))<a href="{{ route('technology-news.index') }}" class="tech-button">Browse all news</a>@endif</div>
            @endforelse
            <div class="tech-pagination">{{ $news->links() }}</div>
        </section>
        <aside class="tech-sidebar" aria-label="Explore technology news">
            @if($trending->isNotEmpty())<section><h2>Trending on Hacker News</h2><p>Ranked by the source’s score in the past 24 hours.</p>@foreach($trending as $story)<article class="tech-trending"><a href="{{ $story->original_url }}" rel="noopener noreferrer">{{ $story->ai_title }}</a><p>{{ $story->publisher_name }} · HN score {{ $story->source_score }}</p></article>@endforeach</section>@endif
            <section><h2>Explore by source</h2><div class="tech-source-list">@forelse($sources as $source)<a href="{{ route('technology-news.index', ['source' => $source->id]) }}"><span>{{ $source->name }}</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>@empty<p>Sources will appear as they become available.</p>@endforelse</div></section>
            <section><h2>A clearer way to catch up</h2><p>AI-assisted summaries are checked against the available source material before publication. Read the original for the publisher’s full reporting.</p><p>Source attribution stays with every story. NASA coverage does not imply endorsement. Mozilla adaptations retain the source’s CC BY-SA licence.</p></section>
        </aside>
    </div>
</div></div>
@endsection
